<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DailyTransactionReportService
{
    /**
     * Builds the owner-facing closing recap from the payment ledger and the
     * three order tables. The cash-method amounts intentionally exclude DP,
     * because DP gets its own figure in the email rather than being counted
     * twice as Tunai/QRIS/Transfer.
     *
     * @return array{date: CarbonImmutable, omzet: float, nota_count: int, tunai: float, qris: float, transfer: float, dp: float, piutang_baru: float, pelunasan_piutang: float, diskon: float, refund: float}
     */
    public function forDate(CarbonImmutable $date): array
    {
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        $payments = OrderPayment::query()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $incoming = $payments->filter(fn (OrderPayment $payment) => (float) $payment->jumlah > 0);
        $regularReceipts = $incoming->whereIn('jenis', ['lunas', 'pelunasan_dp', 'pelunasan_hutang', 'nota_pengganti']);
        $dp = (float) $incoming->where('jenis', 'dp')->sum('jumlah');

        $paidOrders = $incoming
            ->map(fn (OrderPayment $payment) => "{$payment->order_type}-{$payment->order_id}")
            ->unique()
            ->count();

        $ordersPaidToday = $this->ordersPaidBetween($start, $end);

        return [
            'date' => $date,
            'omzet' => (float) $incoming->sum('jumlah'),
            'nota_count' => $paidOrders,
            'tunai' => (float) $regularReceipts->where('cara_bayar', 'tunai')->sum('jumlah'),
            'qris' => (float) $regularReceipts->where('cara_bayar', 'qris')->sum('jumlah'),
            'transfer' => (float) $regularReceipts->where('cara_bayar', 'transfer')->sum('jumlah'),
            'dp' => $dp,
            'piutang_baru' => (float) $ordersPaidToday->where('status_bayar', 'hutang')->sum('jumlah_piutang'),
            'pelunasan_piutang' => (float) $incoming->whereIn('jenis', ['pelunasan_dp', 'pelunasan_hutang'])->sum('jumlah'),
            'diskon' => (float) $ordersPaidToday->sum(fn ($order) => $order->diskonStatus() === 'approved' ? $order->diskonNominal() : 0),
            'refund' => abs((float) $payments->where('jenis', 'refund')->where('jumlah', '<', 0)->sum('jumlah')),
        ];
    }

    private function ordersPaidBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return collect([OrderIndoor::class, OrderOutdoor::class, OrderArtwork::class])
            ->flatMap(fn (string $model) => $model::query()->whereBetween('dibayar_at', [$start, $end])->get());
    }
}
