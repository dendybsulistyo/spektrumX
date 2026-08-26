<?php

namespace App\Services;

use App\Models\JurnalEntry;
use App\Models\PengaturanKeuangan;
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
    private ?float $salesTaxRate = null;

    /** @var \Illuminate\Support\Collection<int, string>|null */
    private ?\Illuminate\Support\Collection $postableAccounts = null;

    public const AKUN_KAS_TUNAI = '11100';

    public const AKUN_KAS_BANK = '11101'; // QRIS/Transfer — both are bank-mediated, not physical cash

    public const AKUN_PIUTANG_DAGANG = '11102';

    public const AKUN_HUTANG_DAGANG = '21100';

    public const AKUN_PPN_MASUKAN = '11600';

    public const AKUN_UANG_MUKA_PENJUALAN = '27100';

    public const AKUN_PENJUALAN = '41000';

    public const AKUN_PPN_KELUARAN = '22105';

    public const AKUN_GAJI = '61001';

    /**
     * Pengeluaran kategori => account code. bahan_baku goes to COGS (51001)
     * rather than the 60000 operating-expense series since it's a direct
     * production cost, not overhead.
     */
    public const AKUN_PENGELUARAN_KATEGORI = [
        'bahan_baku' => '53000',
        'gaji' => '61001',
        'listrik_air' => '63004',
        'sewa' => '63014',
        'transportasi' => '62002',
        'perawatan_alat' => '63012',
        'lain_lain' => '63014',
    ];

    public static function akunKasFor(?string $caraBayar): string
    {
        return $caraBayar === 'tunai' ? self::AKUN_KAS_TUNAI : self::AKUN_KAS_BANK;
    }

    public static function kodeBantuCustomer(?string $customerCode): string
    {
        if (! $customerCode) {
            return '';
        }

        return (string) (DB::table('accounting_customer_profiles')
            ->where('customer_kd', $customerCode)
            ->value('kode_bantu') ?: $customerCode);
    }

    /**
     * Break a selling price that already includes PPN into the exact DPP and
     * PPN Keluaran posting required by the Ledger Spektra chart of accounts.
     * The tax rate remains configurable; the workbook's January 2026 rate is
     * the default 11% carried by PengaturanKeuangan.
     *
     * @return array<int, array{akun: string, debet?: float, kredit?: float}>
     */
    public function salesCreditLines(float $total): array
    {
        $rate = $this->salesTaxRate ??= (float) PengaturanKeuangan::current()->tarif_ppn_default;
        $dpp = $rate > 0 ? round($total / (1 + $rate / 100)) : $total;
        $ppn = round($total - $dpp);

        $lines = [['akun' => self::AKUN_PENJUALAN, 'kredit' => $dpp]];

        if ($ppn > 0) {
            $lines[] = ['akun' => self::AKUN_PPN_KELUARAN, 'kredit' => $ppn];
        }

        return $lines;
    }

    /** @return array<int, array{akun: string, debet?: float, kredit?: float}> */
    public function salesDebitLines(float $total): array
    {
        return array_map(static fn (array $line) => [
            'akun' => $line['akun'],
            'debet' => $line['kredit'],
        ], $this->salesCreditLines($total));
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

        $postableAccounts = $this->postableAccounts ??= DB::table('am__')
            ->whereIn('TipeDK', ['D', 'K'])
            ->pluck('NoAkun');

        $invalidAccount = collect($lines)->pluck('akun')->first(fn (string $account) => ! $postableAccounts->contains($account));

        if ($invalidAccount) {
            throw new InvalidArgumentException("Kode akun {$invalidAccount} tidak ditemukan atau bukan akun yang dapat diposting.");
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
