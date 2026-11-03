<?php

namespace App\Console\Commands;

use App\Services\OrderLateLogService;
use Illuminate\Console\Command;

class BackfillOrderTelat extends Command
{
    protected $signature = 'telat:backfill {--ulang : hapus semua catatan lalu hitung ulang (setelah aturan telat berubah)}';

    protected $description = 'Isi history telat order lama (perkiraan dari waktu Bungkus). Aman dijalankan ulang.';

    public function handle(OrderLateLogService $lateLogs): int
    {
        if (! $lateLogs->isAvailable()) {
            $this->error('Tabel order_late_logs belum ada; jalankan migrasi dulu.');

            return self::FAILURE;
        }

        if ($this->option('ulang')) {
            $this->line($lateLogs->reset().' catatan lama dihapus.');
        }

        $total = $lateLogs->backfill(function (string $type, int $count) {
            if ($count > 0) {
                $this->line("  {$type}: +{$count}");
            }
        });
        $this->info("{$total} order lama ditandai pernah telat (perkiraan).");

        $hasil = $lateLogs->record();
        $this->info("{$hasil['opened']} order yang sedang telat dicatat.");

        return self::SUCCESS;
    }
}
