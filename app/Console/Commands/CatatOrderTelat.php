<?php

namespace App\Console\Commands;

use App\Services\OrderLateLogService;
use Illuminate\Console\Command;

class CatatOrderTelat extends Command
{
    protected $signature = 'telat:catat';

    protected $description = 'Catat order yang telat (> 24 jam belum Siap Diambil) dan tutup catatan order yang sudah siap';

    public function handle(OrderLateLogService $lateLogs): int
    {
        if (! $lateLogs->isAvailable()) {
            $this->line('Tabel order_late_logs belum ada; jalankan migrasi dulu.');

            return self::SUCCESS;
        }

        $hasil = $lateLogs->record();
        $this->info("{$hasil['opened']} order telat baru dicatat, {$hasil['resolved']} catatan ditutup.");

        return self::SUCCESS;
    }
}
