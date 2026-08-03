<?php

namespace App\Services;

use App\Models\JurnalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posts double-entry journal lines into the legacy `am` ledger table (see
 * app/Models/JurnalEntry.php). Every call to post() must balance — total
 * debit === total credit — which is what makes this a real double-entry
 * book rather than just another activity log.
 *
 * Account codes used across the app's write-side (Kasir, Pengeluaran,
 * Payroll) are centralized here so there's one place to look up "which
 * account does X post to" instead of magic numbers scattered around.
 */
class AccountingService
{
    public const AKUN_KAS_TUNAI = '11101';

    public const AKUN_KAS_BANK = '11102'; // QRIS/Transfer — both are bank-mediated, not physical cash

    public const AKUN_PIUTANG_DAGANG = '11301';

    public const AKUN_PENJUALAN = '40001';

    public const AKUN_GAJI = '60001';

    /**
     * Pengeluaran kategori => account code. bahan_baku goes to COGS (51001)
     * rather than the 60000 operating-expense series since it's a direct
     * production cost, not overhead.
     */
    public const AKUN_PENGELUARAN_KATEGORI = [
        'bahan_baku' => '51001',
        'gaji' => '60001',
        'listrik_air' => '60004',
        'sewa' => '60007',
        'transportasi' => '60003',
        'perawatan_alat' => '60099',
        'lain_lain' => '60099',
    ];

    public static function akunKasFor(?string $caraBayar): string
    {
        return $caraBayar === 'tunai' ? self::AKUN_KAS_TUNAI : self::AKUN_KAS_BANK;
    }

    /**
     * Reverses a previously-posted transaction by re-posting its lines with
     * debit/credit swapped — the standard "reversing entry" approach, since
     * the ledger is append-only and past rows are never edited or deleted.
     * No-ops quietly if the NoTrans is empty/unknown (e.g. a pengeluaran row
     * created before this integration existed, with no journal to reverse).
     */
    public function reverse(?string $noTrans, string $keterangan): void
    {
        if (! $noTrans) {
            return;
        }

        $original = JurnalEntry::where('NoTrans', $noTrans)->get();

        if ($original->isEmpty()) {
            return;
        }

        $lines = $original->map(fn (JurnalEntry $e) => [
            'akun' => $e->NoAkun,
            'debet' => (float) $e->Kredit,
            'kredit' => (float) $e->Debet,
            'kd_bantu' => $e->KdBantu,
        ])->all();

        $this->post(now()->format('Y-m-d'), $original->first()->Bukti, $keterangan, $lines);
    }

    /**
     * @param  array<int, array{akun: string, debet?: float, kredit?: float, kd_bantu?: string}>  $lines
     * @return string The generated NoTrans, for callers that need to reverse this posting later.
     */
    public function post(string $tanggal, string $bukti, string $keterangan, array $lines): string
    {
        $totalDebet = array_sum(array_column($lines, 'debet'));
        $totalKredit = array_sum(array_column($lines, 'kredit'));

        // Floating point on money — compare to the cent, not exactly, so a
        // rounding artifact several decimals deep doesn't block posting.
        if (abs($totalDebet - $totalKredit) > 0.01) {
            throw new InvalidArgumentException("Jurnal tidak balance: debet {$totalDebet} != kredit {$totalKredit}.");
        }

        if ($totalDebet <= 0) {
            throw new InvalidArgumentException('Jurnal tidak boleh bernilai nol.');
        }

        // Fits the legacy column's varchar(14) exactly: 6-digit date +
        // 6-digit time + 2-digit random tiebreaker for same-second posts.
        $noTrans = now()->format('ymdHis').rand(10, 99);
        $perio = substr(str_replace('-', '', $tanggal), 0, 6);

        DB::transaction(function () use ($lines, $tanggal, $bukti, $keterangan, $noTrans, $perio) {
            foreach ($lines as $line) {
                JurnalEntry::create([
                    'Perio' => $perio,
                    'NoTrans' => $noTrans,
                    'TgTrans' => $tanggal,
                    'Bukti' => mb_substr($bukti, 0, 20),
                    'KetMT' => mb_substr($keterangan, 0, 60),
                    'NoAkun' => $line['akun'],
                    'Debet' => $line['debet'] ?? 0,
                    'Kredit' => $line['kredit'] ?? 0,
                    'KdBantu' => mb_substr($line['kd_bantu'] ?? '', 0, 10),
                ]);
            }
        });

        return $noTrans;
    }
}
