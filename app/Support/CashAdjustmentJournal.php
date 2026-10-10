<?php

namespace App\Support;

use App\Models\CashDailyEntry;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

/**
 * Jurnal untuk Penyesuaian Kas. Arah kas mengikuti entri (Debet = kas masuk,
 * Kredit = kas keluar); akun lawan mengikuti jenis penyesuaian.
 */
class CashAdjustmentJournal
{
    public const DEFAULT_EXPENSE_ACCOUNT = '63014';

    /** Sumber dana Setor Tunai → akun lawan. */
    public const CASH_SOURCES = [
        'tarik_bank' => ['label' => 'Tarik dari Bank', 'account' => AccountingService::AKUN_KAS_BANK],
        'modal' => ['label' => 'Setoran Modal Pemilik', 'account' => '31000'],
    ];

    public const TYPE_LABELS = [
        'setor_tunai' => 'Setor Tunai',
        'setor_bank' => 'Setor ke Bank',
        'pengeluaran' => 'Pengeluaran',
    ];

    /** Akun beban yang dapat diposting (bukan akun kepala). */
    public static function expenseAccounts()
    {
        return DB::table('am__')->where('NoAkun', 'like', '6%')->whereIn('TipeDK', ['D', 'K'])
            ->orderBy('NoAkun')->get(['NoAkun', 'NmAkun']);
    }

    /** Tebak jenis dari keterangan untuk entri lama yang belum menyimpan jenis. */
    public static function inferType(CashDailyEntry $entry): ?string
    {
        if ($entry->adjustment_type) {
            return $entry->adjustment_type;
        }
        foreach (self::TYPE_LABELS as $type => $label) {
            if (str_starts_with(mb_strtolower((string) $entry->keterangan), mb_strtolower($label))) {
                return $type;
            }
        }

        return null;
    }

    /** Akun lawan default untuk entri lama. */
    public static function defaultCounterAccount(string $type): string
    {
        return match ($type) {
            'pengeluaran' => self::DEFAULT_EXPENSE_ACCOUNT,
            default => AccountingService::AKUN_KAS_BANK, // setor_tunai (tarik dari bank) & setor_bank
        };
    }

    /** Posting jurnal untuk satu entri; mengembalikan nomor jurnal. */
    public static function post(AccountingService $accounting, CashDailyEntry $entry, string $counterAccount, string $typeLabel): string
    {
        $amount = (float) $entry->debet > 0 ? (float) $entry->debet : (float) $entry->kredit;
        $cashIn = (float) $entry->debet > 0;
        $lines = $cashIn
            ? [['akun' => AccountingService::AKUN_KAS_TUNAI, 'debet' => $amount], ['akun' => $counterAccount, 'kredit' => $amount]]
            : [['akun' => $counterAccount, 'debet' => $amount], ['akun' => AccountingService::AKUN_KAS_TUNAI, 'kredit' => $amount]];

        return $accounting->post(
            $entry->tanggal->toDateString(),
            mb_substr($entry->no_nota ?: 'PENYESUAIAN-KAS', 0, 30),
            mb_substr('Penyesuaian Kas - '.$entry->keterangan, 0, 200),
            $lines,
        );
    }
}
