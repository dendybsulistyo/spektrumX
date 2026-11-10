<?php

namespace App\Console\Commands;

use App\Services\OrderDocumentService;
use Illuminate\Console\Command;

/**
 * Invoice susulan untuk SO DP / Hutang Plafon yang masih di Pengambilan (belum
 * disimpan) dan belum punya INV, mengikuti aturan "INV terbit bersamaan SO"
 * (10 Nov 2026). Tanggal invoice = tanggal SO; saat Simpan diganti tanggal Simpan.
 *
 *   php artisan invoice:so-susulan            → hanya tampilkan daftar (tidak mengubah apa pun)
 *   php artisan invoice:so-susulan --jalankan → benar-benar terbitkan invoice
 */
class TerbitkanInvoiceSoSusulan extends Command
{
    protected $signature = 'invoice:so-susulan {--jalankan : Benar-benar terbitkan invoice}';

    protected $description = 'Terbitkan INV untuk SO DP / Plafon yang masih di Pengambilan dan belum punya invoice';

    public function handle(OrderDocumentService $documents): int
    {
        $baris = [];
        $terbit = 0;

        foreach (OrderDocumentService::MODELS as $type => $model) {
            $table = (new $model)->getTable();
            $orders = $model::query()->with('customer.limit')
                ->whereIn('status_bayar', ['dp', 'hutang'])
                ->where('status', '!=', 'batal')->whereNull('invoice_voided_at')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('order_documents')
                    ->where('kind', 'inv')->where('order_type', $type)
                    ->whereColumn('order_documents.order_id', $table.'.id'))
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('sales_transaction_archives')
                    ->where('order_type', $type)
                    ->whereColumn('sales_transaction_archives.order_id', $table.'.id'))
                ->orderBy('TglOrder')->orderBy('id')->get();

            foreach ($orders as $order) {
                $nomor = '-';
                if ($this->option('jalankan')) {
                    $invoice = $documents->issueForSalesOrder($order, $order->TglOrder ?? now());
                    $nomor = $invoice?->number ?? 'dilewati';
                    $terbit += $invoice ? 1 : 0;
                }
                $baris[] = [$order->NoOrder, $order->customer?->NmCust ?? $order->KdCust, strtoupper($order->status_bayar),
                    substr((string) $order->TglOrder, 0, 10), $nomor];
            }
        }

        $this->table(['SO', 'Customer', 'Status', 'Tgl SO', 'Invoice'], $baris);
        $this->info($this->option('jalankan')
            ? "Invoice diterbitkan: {$terbit} dari ".count($baris).' order.'
            : count($baris).' order akan diterbitkan invoice. Jalankan dengan --jalankan untuk menerbitkan.');

        return self::SUCCESS;
    }
}
