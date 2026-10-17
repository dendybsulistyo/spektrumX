<?php

namespace App\Console\Commands;

use App\Models\OrderDocument;
use App\Services\OrderDocumentService;
use Illuminate\Console\Command;

/**
 * Invoice VIP susulan untuk order hutang VIP yang sudah ada sebelum aturan
 * "invoice VIP terbit bersamaan DO" berlaku (mulai Oktober 2026; data
 * sebelumnya hanya demo).
 *
 *   php artisan invoice:vip-susulan                 → hanya tampilkan daftar (tidak mengubah apa pun)
 *   php artisan invoice:vip-susulan --apply         → terbitkan untuk order yang SUDAH ber-DO (tanggal = DO pertama)
 *   php artisan invoice:vip-susulan --semua --apply → juga order yang belum ber-DO (tanggal = sekarang)
 */
class TerbitkanInvoiceVipSusulan extends Command
{
    protected $signature = 'invoice:vip-susulan {--mulai=2026-10-01 : TglOrder paling awal} {--semua : Sertakan order yang belum ber-DO} {--apply : Benar-benar terbitkan invoice}';

    protected $description = 'Terbitkan invoice susulan untuk tagihan VIP (status hutang) sesuai aturan invoice VIP bersamaan DO';

    public function handle(OrderDocumentService $documents): int
    {
        $mulai = $this->option('mulai');
        $baris = [];
        $terbit = 0;

        foreach (OrderDocumentService::MODELS as $type => $model) {
            $orders = $model::query()->with('customer.limit')
                ->where('status_bayar', 'hutang')->where('jumlah_piutang', '>', 0)
                ->where('status', '!=', 'batal')->whereNull('invoice_voided_at')
                ->where('TglOrder', '>=', $mulai)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('order_documents')
                    ->where('kind', 'inv')->where('order_type', $type)
                    ->whereColumn('order_documents.order_id', (new $model)->getTable().'.id'))
                ->orderBy('TglOrder')->orderBy('id')->get();

            foreach ($orders as $order) {
                if (! $order->customer?->isVip) {
                    continue;
                }
                $doPertama = OrderDocument::query()->where('kind', 'do')->where('order_type', $type)
                    ->where('order_id', $order->id)->orderBy('issued_at')->value('issued_at');
                if (! $doPertama && ! $this->option('semua')) {
                    continue;
                }

                $nomor = '-';
                if ($this->option('apply')) {
                    $invoice = $documents->issueCreditInvoice($order, $doPertama ?? now(), 'vip_credit_susulan');
                    $nomor = $invoice?->number ?? 'gagal';
                    $terbit += $invoice ? 1 : 0;
                }
                $baris[] = [$order->NoOrder, $order->customer->NmCust, number_format((float) $order->jumlah_piutang, 0, ',', '.'),
                    $doPertama ? substr((string) $doPertama, 0, 16) : '(belum DO)', $nomor];
            }
        }

        $this->table(['SO', 'Customer', 'Sisa piutang', 'DO pertama', 'Invoice'], $baris);
        $this->info($this->option('apply')
            ? "Invoice VIP diterbitkan: {$terbit} dari ".count($baris).' order.'
            : count($baris).' order akan diterbitkan invoice. Jalankan dengan --apply untuk menerbitkan.');

        return self::SUCCESS;
    }
}
