<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Invoice yang sudah disimpan di Pengambilan → Transaksi sebelum aturan
 * "tanggal invoice = saat Simpan" (5 Nov 2026) berlaku: tanggalnya disamakan
 * dengan archived_at yang sudah tercatat.
 *
 *   php artisan spektrum:samakan-tanggal-invoice-simpan            → hanya tampilkan
 *   php artisan spektrum:samakan-tanggal-invoice-simpan --jalankan
 */
class SamakanTanggalInvoiceDenganSimpan extends Command
{
    protected $signature = 'spektrum:samakan-tanggal-invoice-simpan {--jalankan : benar-benar menyimpan (tanpa ini hanya menampilkan)}';

    protected $description = 'Samakan tanggal invoice dengan tanggal Simpan di Pengambilan untuk transaksi yang sudah tersimpan';

    public function handle(): int
    {
        $rows = DB::table('order_documents as d')
            ->join('sales_transaction_archives as a', fn ($join) => $join
                ->on('a.order_type', '=', 'd.order_type')->on('a.order_id', '=', 'd.order_id'))
            ->where('d.kind', 'inv')
            ->whereNotNull('a.archived_at')
            ->whereColumn('d.issued_at', '<>', 'a.archived_at')
            ->orderBy('a.archived_at')
            ->get(['d.id', 'd.number', 'd.issued_at', 'a.archived_at']);

        if ($rows->isEmpty()) {
            $this->info('Tidak ada invoice yang perlu disamakan.');

            return self::SUCCESS;
        }

        $this->table(['No. Invoice', 'Tanggal sekarang', 'Menjadi (tanggal Simpan)'],
            $rows->map(fn ($row) => [$row->number, $row->issued_at, $row->archived_at])->all());
        $this->line($rows->count().' invoice.');

        if (! $this->option('jalankan')) {
            $this->warn('Belum disimpan. Jalankan ulang dengan --jalankan untuk menyimpan.');

            return self::SUCCESS;
        }
        if (! $this->confirm('Ubah tanggal '.$rows->count().' invoice di atas?')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                DB::table('order_documents')->where('id', $row->id)
                    ->update(['issued_at' => $row->archived_at, 'updated_at' => now()]);
            }
        });
        $this->info($rows->count().' invoice sudah disamakan dengan tanggal Simpan.');

        return self::SUCCESS;
    }
}
