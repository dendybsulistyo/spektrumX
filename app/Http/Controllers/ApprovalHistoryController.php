<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\OrderStatusNote;
use App\Models\ReprintRequest;
use App\Models\User;
use App\Services\OrderDocumentService;
use App\Services\ReprintGate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * History Approval: siapa yang meminta dan siapa yang memutuskan, untuk
 * Pembatalan Order, Diskon, Hutang VIP, dan Cetak Ulang.
 */
class ApprovalHistoryController extends Controller
{
    public const KIND_LABELS = [
        'pembatalan' => 'Pembatalan Order',
        'diskon' => 'Diskon',
        'hutang' => 'Hutang VIP',
        'cetak_ulang' => 'Cetak Ulang',
    ];

    public const STATUS_LABELS = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    public function index(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $kind = array_key_exists((string) $request->query('jenis'), self::KIND_LABELS) ? $request->query('jenis') : null;
        $status = array_key_exists((string) $request->query('status'), self::STATUS_LABELS) ? $request->query('status') : null;
        $search = mb_strtolower(trim((string) $request->query('q')));
        $range = [$from.' 00:00:00', $to.' 23:59:59'];

        $rows = collect()
            ->concat(! $kind || $kind === 'pembatalan' ? $this->cancellations($range) : [])
            ->concat(! $kind || in_array($kind, ['diskon', 'hutang'], true) ? $this->discountAndDebt($range, $kind) : [])
            ->concat(! $kind || $kind === 'cetak_ulang' ? $this->reprints($range) : []);

        $orders = $this->orders($rows);
        $users = User::query()->whereIn('id', $rows->pluck('requested_by')->merge($rows->pluck('decided_by'))->filter()->unique())
            ->pluck('name', 'id');

        $rows = $rows->map(function (array $row) use ($orders, $users) {
            $order = $orders[$row['order_type'].'-'.$row['order_id']] ?? null;

            return (object) $row + [
                'no_order' => $order?->NoOrder ?? '#'.$row['order_id'],
                'customer' => $order?->customer?->NmCust ?? '-',
                'requested_name' => $users[$row['requested_by']] ?? '-',
                'decided_name' => $users[$row['decided_by']] ?? null,
            ];
        })
            ->when($status, fn (Collection $rows) => $rows->where('status', $status))
            ->when($search !== '', fn (Collection $rows) => $rows->filter(fn ($row) => str_contains(mb_strtolower(
                $row->no_order.' '.$row->customer.' '.$row->requested_name.' '.$row->decided_name.' '.$row->reason.' '.$row->detail
            ), $search)))
            ->sortByDesc(fn ($row) => $row->requested_at?->format('YmdHis'))
            ->values();

        return view('approval-history.index', compact('rows', 'from', 'to', 'kind', 'status') + ['search' => $request->query('q')]);
    }

    /**
     * Setiap "diajukan" dipasangkan dengan keputusan (disetujui/ditolak)
     * berikutnya pada order yang sama.
     *
     * @param  array{0: string, 1: string}  $range
     * @return Collection<int, array<string, mixed>>
     */
    private function cancellations(array $range): Collection
    {
        $requests = OrderStatusNote::query()->where('stage', 'pembatalan')->where('action', 'diajukan')
            ->whereBetween('created_at', $range)->get();
        if ($requests->isEmpty()) {
            return collect();
        }

        $decisions = OrderStatusNote::query()->where('stage', 'pembatalan')->whereIn('action', ['disetujui', 'ditolak'])
            ->whereIn('order_id', $requests->pluck('order_id')->unique())
            ->where('created_at', '>=', $range[0])
            ->orderBy('created_at')->orderBy('id')->get()
            ->groupBy(fn ($note) => $note->order_type.'-'.$note->order_id);

        return $requests->map(function ($note) use ($decisions) {
            $decision = ($decisions[$note->order_type.'-'.$note->order_id] ?? collect())
                ->first(fn ($candidate) => $candidate->created_at >= $note->created_at);

            return [
                'kind' => 'pembatalan', 'order_type' => $note->order_type, 'order_id' => $note->order_id,
                'detail' => null, 'reason' => $note->catatan,
                'requested_by' => $note->user_id, 'requested_at' => Carbon::parse($note->created_at),
                'status' => match ($decision?->action) {
                    'disetujui' => 'approved',
                    'ditolak' => 'rejected',
                    default => 'pending',
                },
                'decided_by' => $decision?->user_id, 'decided_at' => $decision ? Carbon::parse($decision->created_at) : null,
            ];
        });
    }

    /**
     * Diskon & Hutang VIP: dari approval_requests (lengkap sejak 5 Nov 2026),
     * ditambah pengajuan terakhir yang tersimpan di order untuk data sebelumnya.
     *
     * @param  array{0: string, 1: string}  $range
     * @return Collection<int, array<string, mixed>>
     */
    private function discountAndDebt(array $range, ?string $kind): Collection
    {
        $logged = ApprovalRequest::query()->whereBetween('requested_at', $range)
            ->when($kind, fn ($query) => $query->where('kind', $kind))->get();
        $rows = $logged->map(fn (ApprovalRequest $row) => [
            'kind' => $row->kind, 'order_type' => $row->order_type, 'order_id' => $row->order_id,
            'detail' => $row->detail, 'reason' => $row->reason,
            'requested_by' => $row->requested_by, 'requested_at' => $row->requested_at,
            'status' => $row->status, 'decided_by' => $row->decided_by, 'decided_at' => $row->decided_at,
        ]);
        $loggedKeys = ApprovalRequest::query()->get(['kind', 'order_type', 'order_id'])
            ->map(fn ($row) => $row->kind.'-'.$row->order_type.'-'.$row->order_id)->flip();

        foreach (OrderDocumentService::MODELS as $type => $model) {
            foreach (['diskon', 'hutang'] as $legacyKind) {
                if ($kind && $kind !== $legacyKind) {
                    continue;
                }
                $model::query()->whereBetween($legacyKind.'_requested_at', $range)->get()
                    ->reject(fn ($order) => $loggedKeys->has($legacyKind.'-'.$type.'-'.$order->id))
                    ->each(function ($order) use ($legacyKind, $type, &$rows) {
                        $approvedAt = $order->{$legacyKind.'_approved_at'};
                        $rejectedAt = $order->{$legacyKind.'_rejected_at'};
                        $rows->push([
                            'kind' => $legacyKind, 'order_type' => $type, 'order_id' => $order->id,
                            'detail' => $legacyKind === 'diskon' ? $order->diskonRequestedLabel() : null,
                            'reason' => $legacyKind === 'diskon' ? $order->diskon_alasan : $order->hutang_catatan,
                            'requested_by' => $order->{$legacyKind.'_requested_by'},
                            'requested_at' => Carbon::parse($order->{$legacyKind.'_requested_at'}),
                            'status' => $approvedAt ? 'approved' : ($rejectedAt ? 'rejected' : 'pending'),
                            'decided_by' => $approvedAt ? $order->{$legacyKind.'_approved_by'} : ($rejectedAt ? $order->{$legacyKind.'_rejected_by'} : null),
                            'decided_at' => $approvedAt || $rejectedAt ? Carbon::parse($approvedAt ?? $rejectedAt) : null,
                        ]);
                    });
            }
        }

        return $rows;
    }

    /**
     * @param  array{0: string, 1: string}  $range
     * @return Collection<int, array<string, mixed>>
     */
    private function reprints(array $range): Collection
    {
        return ReprintRequest::query()->whereBetween('created_at', $range)->get()
            ->map(fn (ReprintRequest $row) => [
                'kind' => 'cetak_ulang', 'order_type' => $row->order_type, 'order_id' => $row->order_id,
                'detail' => ReprintGate::KIND_LABELS[$row->kind] ?? strtoupper($row->kind), 'reason' => $row->reason,
                'requested_by' => $row->requested_by, 'requested_at' => $row->created_at,
                // "used" = disetujui lalu sudah dipakai untuk mencetak.
                'status' => $row->status === 'used' ? 'approved' : $row->status,
                'decided_by' => $row->decided_by, 'decided_at' => $row->decided_at,
            ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function orders(Collection $rows): array
    {
        $orders = [];
        foreach ($rows->groupBy('order_type') as $type => $typeRows) {
            $model = OrderDocumentService::MODELS[$type] ?? null;
            if (! $model) {
                continue;
            }
            $model::query()->with('customer')->whereIn('id', $typeRows->pluck('order_id')->unique())
                ->get(['id', 'NoOrder', 'KdCust'])
                ->each(function ($order) use ($type, &$orders) {
                    $orders[$type.'-'.$order->id] = $order;
                });
        }

        return $orders;
    }
}
