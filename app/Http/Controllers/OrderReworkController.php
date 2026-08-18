<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Services\AccountingService;
use App\Services\CustomerCreditService;
use App\Support\ResolvesOrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderReworkController extends Controller
{
    use ResolvesOrderType;

    /**
     * Pipeline order for the "Ulang" (rework) target — lower index is
     * earlier in production. Used to block picking a target_stage that's
     * further along than from_stage; going backward (or resubmitting the
     * same stage) is the whole point of "Ulang", going forward isn't.
     */
    private const STAGE_ORDER = ['desain' => 0, 'cetak' => 1, 'finishing' => 2, 'qc' => 3, 'bungkus' => 4];

    public function __construct(
        private readonly AccountingService $accounting,
        private readonly CustomerCreditService $creditService,
    ) {}

    /**
     * Any operator who can manage at least one production stage can raise a
     * rework request from wherever the order currently sits — this is a
     * shared cross-stage action, not gated to one specific stage's
     * permission, since the button appears on all six stage pages.
     * kasir.manage is also allowed since "Batalkan Order" (action=batal) now
     * lives in Kasir instead of on the stage pages.
     */
    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(
            auth()->user()->hasPermission('order-desain.manage')
                || auth()->user()->hasPermission('order-cetak.manage')
                || auth()->user()->hasPermission('order-finishing.manage')
                || auth()->user()->hasPermission('order-qc.manage')
                || auth()->user()->hasPermission('order-bungkus.manage')
                || auth()->user()->hasPermission('pengambilan.manage')
                || auth()->user()->hasPermission('kasir.manage'),
            403
        );

        $order = $this->resolveOrder($type, $id);

        abort_if(
            OrderReworkRequest::forOrder($type, $id)->pending()->exists(),
            422,
            'Order ini sudah punya pengajuan yang menunggu persetujuan.'
        );

        $data = $request->validate([
            'action' => ['required', 'in:ulang,batal'],
            'from_stage' => ['required_if:action,ulang', 'nullable', 'in:desain,cetak,finishing,qc,bungkus'],
            'target_stage' => [
                'required_if:action,ulang', 'nullable', 'in:desain,cetak,finishing,qc,bungkus',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('action') !== 'ulang') {
                        return;
                    }

                    $from = self::STAGE_ORDER[$request->input('from_stage')] ?? null;
                    $target = self::STAGE_ORDER[$value] ?? null;

                    if ($from !== null && $target !== null && $target > $from) {
                        $fail('Ulang hanya boleh mundur ke tahap sebelumnya, tidak boleh maju.');
                    }
                },
            ],
            'qty' => ['nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        // Qty can now sit at several stages of the same order at once (see
        // HasStageProgress::recalculateStatus()), so $order->status — the
        // LEAST advanced stage across all items — is not necessarily the
        // stage the operator is actually looking at. Trust which stage
        // page the "Ulang" button was clicked from (from_stage, submitted
        // by the order-rework component) instead, but only if that order
        // genuinely still has qty sitting there — guards against a stale
        // page (qty already moved on) or a tampered request.
        $qty = null;

        if ($data['action'] === 'ulang') {
            $totalAtFromStage = $order->detailItems()->sum(fn ($item) => $item->qtyAt($data['from_stage']));
            abort_if($totalAtFromStage === 0, 422, 'Order ini sudah tidak punya qty di tahap tersebut — halaman mungkin sudah berubah, muat ulang dulu.');

            // Not submitted (older/plain clients) defaults to "everything
            // sitting there", matching the previous whole-order behavior.
            $qty = min($data['qty'] ?? $totalAtFromStage, $totalAtFromStage);
        }

        OrderReworkRequest::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'current_stage' => $data['action'] === 'ulang' ? $data['from_stage'] : $order->status,
            'action' => $data['action'],
            'target_stage' => $data['action'] === 'ulang' ? $data['target_stage'] : null,
            'qty' => $qty,
            'reason' => $data['reason'],
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan dikirim, menunggu persetujuan.');
    }

    public function approve(OrderReworkRequest $orderReworkRequest): RedirectResponse
    {
        abort_if($orderReworkRequest->status !== 'pending', 422, 'Pengajuan ini sudah diproses.');

        $order = $this->resolveOrder($orderReworkRequest->order_type, $orderReworkRequest->order_id);

        $newStatus = $orderReworkRequest->action === 'batal' ? 'batal' : $orderReworkRequest->target_stage;

        DB::transaction(function () use ($order, $orderReworkRequest, $newStatus) {
            if ($orderReworkRequest->action === 'batal') {
                $order->update(['status' => $newStatus]);
                $this->refundCancelledOrder($order, $orderReworkRequest->order_type);
            } else {
                // Header `status` is derived from item-level qty buckets
                // (see HasStageProgress::recalculateStatus()) — moving an
                // order "back a stage" means actually pulling whatever qty
                // is still sitting at current_stage's bucket back into
                // target_stage's bucket, not just overwriting the header
                // column (which recalculateStatus() would silently
                // overwrite again on the next item advance anyway).
                $fromCol = "qty_{$orderReworkRequest->current_stage}";
                $toCol = "qty_{$newStatus}";

                // A request's qty may be less than everything sitting at
                // this stage (partial rework) — null means "everything"
                // (pending requests raised before this column existed).
                // With more than one line item, whichever items still have
                // qty here get filled first-come-first-served until the
                // requested amount is used up; a single-item order (by far
                // the common case) just moves exactly what was asked.
                $remaining = $orderReworkRequest->qty ?? PHP_INT_MAX;

                foreach ($order->detailItems() as $item) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = (int) $item->{$fromCol};
                    $move = min($available, $remaining);

                    if ($move > 0) {
                        $item->decrement($fromCol, $move);
                        $item->increment($toCol, $move);
                        $remaining -= $move;
                    }
                }

                $order->recalculateStatus();
            }

            $orderReworkRequest->update([
                'status' => 'approved',
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            OrderStatusNote::create([
                'order_type' => $orderReworkRequest->order_type,
                'order_id' => $order->id,
                'qty' => $orderReworkRequest->action === 'ulang' ? $orderReworkRequest->qty : null,
                'stage' => $orderReworkRequest->current_stage,
                'action' => $orderReworkRequest->action === 'batal' ? 'dibatalkan' : 'diulang',
                'catatan' => $orderReworkRequest->action === 'batal'
                    ? "Order dibatalkan, uang dikembalikan ke customer (alasan: {$orderReworkRequest->reason})"
                    : ($orderReworkRequest->qty ?? 'Semua').' unit diulang ke tahap '.(OrderReworkRequest::STAGE_LABELS[$orderReworkRequest->target_stage] ?? $orderReworkRequest->target_stage)." (alasan: {$orderReworkRequest->reason})",
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Pengajuan disetujui.');
    }

    /**
     * Refunds whatever the customer already paid on a now-cancelled order —
     * cash back via a reversing journal entry (same pattern as the cashback
     * branch of the nota-pengganti flow in KasirController::bayar()), or,
     * for hutang, simply releases the credit-limit debt since no cash ever
     * moved. belum_bayar orders have nothing to reverse.
     */
    private function refundCancelledOrder(Model $order, string $type): void
    {
        $dibayar = (float) ($order->jumlah_dibayar ?? 0);
        $piutang = (float) ($order->jumlah_piutang ?? 0);

        if ($order->metode_bayar === 'hutang' && $piutang > 0) {
            if ($order->customer?->limit) {
                $this->creditService->reduceHutang($order->customer, $piutang);
            }
        } elseif ($dibayar > 0 && $order->cara_bayar) {
            $kdBantu = $order->customer?->KdCust ?? '';

            // 'campuran' means the original payment was split across
            // methods — reverse it out of the same real methods (tunai/
            // qris/transfer) it actually came from instead of guessing one.
            // order_payments.cara_bayar has no 'campuran' value of its own,
            // so the refund is recorded as one row per real method too.
            $breakdown = $order->cara_bayar === 'campuran'
                ? $this->refundBreakdownByMethod($type, $order->id, $dibayar)
                : [$order->cara_bayar => $dibayar];

            $kasLines = collect($breakdown)
                ->map(fn ($jumlah, $caraBayar) => ['akun' => AccountingService::akunKasFor($caraBayar), 'jumlah' => $jumlah])
                ->groupBy('akun')
                ->map(fn ($rows, $akun) => ['akun' => $akun, 'kredit' => (float) array_sum(array_column($rows->all(), 'jumlah')), 'kd_bantu' => $kdBantu])
                ->values()
                ->all();

            $this->accounting->post(
                now()->format('Y-m-d'),
                $order->NoOrder,
                'Refund pembatalan order '.$order->NoOrder,
                [
                    ['akun' => AccountingService::AKUN_PENJUALAN, 'debet' => $dibayar],
                    ...$kasLines,
                ]
            );

            foreach ($breakdown as $caraBayar => $jumlah) {
                OrderPayment::create([
                    'order_type' => $type,
                    'order_id' => $order->id,
                    'jenis' => 'refund',
                    'jumlah' => -$jumlah,
                    'cara_bayar' => $caraBayar,
                    'no_referensi' => null,
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                ]);
            }
        }

        $order->update([
            'jumlah_dibayar' => 0,
            'jumlah_piutang' => 0,
        ]);
    }

    /**
     * Reconstructs the per-method split for a refund when the order's own
     * cara_bayar is 'campuran' — sums the actual OrderPayment rows by
     * method (excluding any prior refunds) and proportions the amount still
     * being refunded across those same methods, so both the journal and the
     * refund's own OrderPayment rows reflect where the money actually came
     * from. Falls back to a single 'transfer' bucket if the ledger somehow
     * has nothing to go on (shouldn't happen for a paid order, but a
     * refund still has to land somewhere).
     *
     * @return array<string, float>
     */
    private function refundBreakdownByMethod(string $type, int $orderId, float $dibayar): array
    {
        $byMethod = OrderPayment::forOrder($type, $orderId)
            ->where('jenis', '!=', 'refund')
            ->get()
            ->groupBy('cara_bayar')
            ->map(fn ($rows) => (float) $rows->sum('jumlah'));

        $totalPaid = (float) $byMethod->sum();

        if ($totalPaid <= 0) {
            return ['transfer' => $dibayar];
        }

        return $byMethod->map(fn ($jumlah) => $jumlah / $totalPaid * $dibayar)->all();
    }

    public function reject(OrderReworkRequest $orderReworkRequest): RedirectResponse
    {
        abort_if($orderReworkRequest->status !== 'pending', 422, 'Pengajuan ini sudah diproses.');

        $orderReworkRequest->update([
            'status' => 'rejected',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan ditolak, order lanjut diproses normal.');
    }
}
