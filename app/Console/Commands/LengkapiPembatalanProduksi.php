<?php

namespace App\Console\Commands;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderReworkRequest;
use Illuminate\Console\Command;

/**
 * Order yang dibatalkan lewat pengajuan Ulang/Batal produksi sebelum 11 Nov 2026 tidak
 * punya data pembatalan resmi (pengaju, penyetuju, tanggal, alasan), sehingga tidak tampil
 * di laporan pembatalan. Data diisi dari pengajuan Batal yang disetujui; uang & jurnal
 * tidak disentuh (sudah diproses saat persetujuan).
 *
 *   php artisan spektrum:lengkapi-batal-produksi              → hanya tampilkan
 *   php artisan spektrum:lengkapi-batal-produksi --jalankan   → simpan
 */
class LengkapiPembatalanProduksi extends Command
{
    protected $signature = 'spektrum:lengkapi-batal-produksi {--jalankan : benar-benar menyimpan (tanpa ini hanya menampilkan)}';

    protected $description = 'Lengkapi data pembatalan resmi untuk order yang dibatalkan lewat Ulang/Batal produksi';

    private const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    public function handle(): int
    {
        $requests = OrderReworkRequest::query()
            ->where('action', 'batal')->where('status', 'approved')
            ->whereIn('order_type', array_keys(self::MODELS))
            ->orderBy('resolved_at')->get();

        $rows = [];
        foreach ($requests as $request) {
            $order = self::MODELS[$request->order_type]::query()
                ->whereKey($request->order_id)->where('status', 'batal')->whereNull('cancel_approved_at')->first();
            if (! $order) {
                continue;
            }

            $rows[] = [$order->NoOrder, $request->resolved_at?->format('d/m/Y H:i') ?? '-', (string) $request->reason];
            if ($this->option('jalankan')) {
                $order->update([
                    'cancel_requested_at' => $order->cancel_requested_at ?? $request->requested_at,
                    'cancel_requested_by' => $order->cancel_requested_by ?? $request->requested_by,
                    'cancel_reason' => $order->cancel_reason ?: $request->reason,
                    'cancel_approved_at' => $request->resolved_at,
                    'cancel_approved_by' => $request->resolved_by,
                ]);
            }
        }

        if ($rows === []) {
            $this->info('Tidak ada order batal produksi yang perlu dilengkapi.');

            return self::SUCCESS;
        }

        $this->table(['No. Order', 'Disetujui', 'Alasan'], $rows);
        $this->info($this->option('jalankan')
            ? count($rows).' order dilengkapi.'
            : count($rows).' order akan dilengkapi. Jalankan ulang dengan --jalankan untuk menyimpan.');

        return self::SUCCESS;
    }
}
