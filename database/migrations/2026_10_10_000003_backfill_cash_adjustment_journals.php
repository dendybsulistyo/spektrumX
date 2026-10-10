<?php

use App\Models\CashDailyEntry;
use App\Services\AccountingService;
use App\Support\CashAdjustmentJournal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Jurnal mundur untuk Penyesuaian Kas lama (sebelum ada jurnal otomatis).
 * - Januari 2026 dilewati (transaksi demo / uji gunggungan).
 * - Entri yang sudah punya jurnal tidak diposting ulang (aman dijalankan ulang).
 * - Periode yang sudah tutup buku dilewati & dicatat di log.
 * Akun lawan default: Pengeluaran → 63014, Setor Tunai/Setor ke Bank → 11101 Bank.
 */
return new class extends Migration
{
    public function up(): void
    {
        $accounting = app(AccountingService::class);
        $summary = ['diposting' => 0, 'januari_dilewati' => 0, 'tutup_buku' => 0, 'jenis_tak_dikenal' => 0, 'gagal' => 0];

        CashDailyEntry::query()->cashAdjustments()->whereNull('journal_number')
            ->orderBy('tanggal')->orderBy('id')
            ->each(function (CashDailyEntry $entry) use ($accounting, &$summary) {
                if ($entry->tanggal->format('Y-m') === '2026-01') {
                    $summary['januari_dilewati']++;

                    return;
                }
                $type = CashAdjustmentJournal::inferType($entry);
                if (! $type || ((float) $entry->debet <= 0 && (float) $entry->kredit <= 0)) {
                    $summary['jenis_tak_dikenal']++;
                    Log::warning('Jurnal mundur penyesuaian kas: jenis tidak dikenali', ['id' => $entry->id, 'keterangan' => $entry->keterangan]);

                    return;
                }
                $counter = $entry->counter_account ?: CashAdjustmentJournal::defaultCounterAccount($type);

                try {
                    DB::transaction(function () use ($accounting, $entry, $type, $counter) {
                        $number = CashAdjustmentJournal::post($accounting, $entry, $counter, CashAdjustmentJournal::TYPE_LABELS[$type]);
                        $entry->forceFill(['adjustment_type' => $type, 'counter_account' => $counter, 'journal_number' => $number])->save();
                    });
                    $summary['diposting']++;
                } catch (InvalidArgumentException $e) {
                    $closed = str_contains($e->getMessage(), 'sudah ditutup');
                    $summary[$closed ? 'tutup_buku' : 'gagal']++;
                    Log::warning('Jurnal mundur penyesuaian kas dilewati', ['id' => $entry->id, 'tanggal' => $entry->tanggal->toDateString(), 'alasan' => $e->getMessage()]);
                }
            });

        Log::info('Jurnal mundur penyesuaian kas selesai', $summary);
        if (defined('STDOUT')) {
            fwrite(STDOUT, '  Jurnal mundur penyesuaian kas: '.json_encode($summary).PHP_EOL);
        }
    }

    public function down(): void
    {
        // Jurnal yang sudah diposting tidak dihapus otomatis — koreksi lewat Jurnal Manual bila perlu.
    }
};
