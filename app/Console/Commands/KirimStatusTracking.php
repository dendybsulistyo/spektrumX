<?php

namespace App\Console\Commands;

use App\Services\OrderTrackingService;
use Illuminate\Console\Command;

class KirimStatusTracking extends Command
{
    protected $signature = 'tracking:kirim';

    protected $description = 'Kirim perubahan status order ke halaman tracking customer (Cloudflare Worker)';

    public function handle(OrderTrackingService $tracking): int
    {
        if (! $tracking->aktif()) {
            $this->line('Tracking nonaktif (TRACKING_URL / TRACKING_SECRET kosong).');

            return self::SUCCESS;
        }

        $hasil = $tracking->kirimPerubahan();

        if ($hasil['gagal']) {
            $this->warn('Gagal mengirim; akan dicoba lagi pada jadwal berikutnya.');

            return self::FAILURE;
        }

        $this->info("Terkirim {$hasil['dikirim']} order".($hasil['ditolak'] ? ", ditolak {$hasil['ditolak']}" : '').'.');

        return self::SUCCESS;
    }
}
