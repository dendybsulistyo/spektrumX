<?php

namespace App\Services;

use App\Models\JurnalEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GunggunganHistoricalJournalService
{
    public function __construct(private readonly AccountingService $accounting) {}

    /** @return Collection<int, object> */
    public function candidates(int $year, int $month): Collection
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();
        $columns = ['id', 'TglOrder', 'NoOrder', 'KdCust', 'total', 'status_bayar', 'cara_bayar', 'jumlah_dibayar'];

        return collect([
            ...DB::table('order_indoor')
                ->join('order_indoor_detail', 'order_indoor_detail.order_indoor_id', '=', 'order_indoor.id')
                ->where('order_indoor_detail.NmProd', 'Gunggungan Januari')
                ->whereBetween('order_indoor.TglOrder', [$start, $end])
                ->whereIn('order_indoor.status_bayar', ['lunas', 'hutang', 'dp'])
                ->get(array_map(fn ($column) => "order_indoor.{$column}", $columns))
                ->map(fn ($row) => (object) (['type' => 'indoor'] + (array) $row)),
        ])->sortBy(['TglOrder', 'NoOrder'])->values();
    }

    /** @return array{candidates:int,ready:int,already_posted:int,total:float} */
    public function summary(int $year, int $month): array
    {
        $orders = $this->candidates($year, $month);
        $posted = JurnalEntry::whereIn('Bukti', $orders->pluck('NoOrder'))->pluck('Bukti')->flip();
        $ready = $orders->reject(fn ($order) => $posted->has($order->NoOrder));

        return ['candidates' => $orders->count(), 'ready' => $ready->count(), 'already_posted' => $orders->count() - $ready->count(), 'total' => (float) $ready->sum(fn ($order) => $order->status_bayar === 'dp' ? $order->jumlah_dibayar : $order->total)];
    }

    /** @return array{imported:int,total:float} */
    public function import(int $year, int $month): array
    {
        $orders = $this->candidates($year, $month);
        $posted = JurnalEntry::whereIn('Bukti', $orders->pluck('NoOrder'))->pluck('Bukti')->flip();
        $imported = 0; $total = 0.0;

        DB::transaction(function () use ($orders, $posted, &$imported, &$total) {
            foreach ($orders as $order) {
                if ($posted->has($order->NoOrder)) continue;

                $amount = (float) ($order->status_bayar === 'dp' ? $order->jumlah_dibayar : $order->total);
                if ($amount <= 0) continue;
                $helper = AccountingService::kodeBantuCustomer($order->KdCust);

                $lines = match ($order->status_bayar) {
                    'hutang' => [
                        ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'debet' => $amount, 'kd_bantu' => $helper],
                        ...$this->accounting->salesCreditLines($amount),
                    ],
                    'dp' => [
                        ['akun' => AccountingService::akunKasFor($order->cara_bayar), 'debet' => $amount, 'kd_bantu' => $helper],
                        ['akun' => AccountingService::AKUN_UANG_MUKA_PENJUALAN, 'kredit' => $amount, 'kd_bantu' => $helper],
                    ],
                    default => [
                        ['akun' => AccountingService::akunKasFor($order->cara_bayar), 'debet' => $amount, 'kd_bantu' => $helper],
                        ...$this->accounting->salesCreditLines($amount),
                    ],
                };

                $this->accounting->post($order->TglOrder, $order->NoOrder, 'Penjualan historis '.$order->NoOrder, $lines);
                $imported++; $total += $amount;
            }
        });

        return compact('imported', 'total');
    }
}
