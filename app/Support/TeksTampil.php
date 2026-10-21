<?php

namespace App\Support;

/**
 * Membersihkan emoji/simbol hiasan dari teks HANYA untuk tampilan
 * (dashboard, laporan). Data di database tidak diubah.
 */
final class TeksTampil
{
    private const POLA = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{2300}-\x{23FF}\x{FE00}-\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]/u';

    public static function bersih(?string $teks): ?string
    {
        if ($teks === null || $teks === '') {
            return $teks;
        }

        $hasil = trim((string) preg_replace('/\s{2,}/u', ' ', (string) preg_replace(self::POLA, '', $teks)));

        // Nama yang isinya hanya emoji tetap ditampilkan apa adanya daripada kosong.
        return $hasil === '' ? $teks : $hasil;
    }
}
