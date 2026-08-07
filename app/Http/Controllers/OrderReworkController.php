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
            'target_stage' => ['required_if:action,ulang', 'nullable', 'in:desain,cetak,finishing,qc,bungkus'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        OrderReworkRequest::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'current_stage' => $order->status,
            'action' => $data['action'],
            'target_stage' => $data['action'] === 'ulang' ? $data['target_stage'] : null,
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
            $order->update(['status' => $newStatus]);

            if ($orderReworkRequest->action === 'batal') {
                $this->refundCancelledOrder($order, $orderReworkRequest->order_type);
            }

            $orderReworkRequest->update([
                'status' => 'approved',
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            OrderStatusNote::create([
                'order_type' => $orderReworkRequest->order_type,
                'order_id' => $order->id,
                'stage' => $orderReworkRequest->current_stage,
                'action' => $orderReworkRequest->action === 'batal' ? 'dibatalkan' : 'diulang',
                'catatan' => $orderReworkRequest->action === 'batal'
                    ? "Order dibatalkan, uang dikembalikan ke customer (alasan: {$orderReworkRequest->reason})"
                    : "Order diulang ke tahap ".(OrderReworkRequest::STAGE_LABELS[$orderReworkRequest->target_stage] ?? $orderReworkRequest->target_stage)." (alasan: {$orderReworkRequest->reason})",
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
