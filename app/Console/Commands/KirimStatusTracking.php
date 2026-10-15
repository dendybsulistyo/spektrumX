<?php

namespace App\Console\Commands;

use App\Services\OrderTrackingService;
use Illuminate\Console\Command;

class KirimStatusTracking extends Command
{
    protected $signature = 'tracking:kirim {--ulang : Kirim ulang SEMUA order bertoken (mis. setelah pindah domain/akun Cloudflare)}';

    protected $description = 'Kirim perubahan status order ke halaman tracking customer (Cloudflare Worker)';

    public function handle(OrderTrackingService $tracking): int
    {
        if (! $tracking->aktif()) {
            $this->line('Tracking nonaktif (TRACKING_URL / TRACKING_SECRET kosong).');

            return self::SUCCESS;
        }

        if ($this->option('ulang')) {
            $jumlah = $tracking->tandaiBelumTerkirim();
            $this->line("{$jumlah} order ditandai untuk dikirim ulang.");
        }

        // Kirim bertahap (maks. 500 per putaran) sampai antrean habis.
        $hasil = ['dikirim' => 0, 'ditolak' => 0, 'gagal' => false];
        do {
            $putaran = $tracking->kirimPerubahan();
            $hasil['dikirim'] += $putaran['dikirim'];
            $hasil['ditolak'] += $putaran['ditolak'];
            $hasil['gagal'] = $putaran['gagal'];
        } while (! $putaran['gagal'] && ($putaran['dikirim'] + $putaran['ditolak']) > 0 && $this->option('ulang'));

        if ($hasil['gagal']) {
            $this->warn('Gagal mengirim; akan dicoba lagi pada jadwal berikutnya.');

            return self::FAILURE;
        }

        $this->info("Terkirim {$hasil['dikirim']} order".($hasil['ditolak'] ? ", ditolak {$hasil['ditolak']}" : '').'.');

        return self::SUCCESS;
    }
}
