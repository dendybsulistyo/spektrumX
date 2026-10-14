<?php

namespace App\Support;

final class Rupiah
{
    public const UNIT_PEMBULATAN = 100;

    /** Round a Rupiah amount up to the next supported Rp100 denomination. */
    public static function bulatkan(float|int $nominal): float
    {
        return ceil($nominal / self::UNIT_PEMBULATAN) * self::UNIT_PEMBULATAN;
    }

    /** Minimum payment thresholds must never fall below their true value. */
    public static function bulatkanKeAtas(float|int $nominal): float
    {
        return ceil($nominal / self::UNIT_PEMBULATAN) * self::UNIT_PEMBULATAN;
    }

    /**
     * Harga satuan untuk tampilan: desimal hanya muncul bila memang ada
     * (0,7 / 3,55 per cm² tetap terbaca; 20000 → "20.000"). Tanpa prefix "Rp".
     */
    public static function harga(float|int|string|null $nominal): string
    {
        $nilai = (float) $nominal;
        $teks = number_format($nilai, 2, ',', '.');

        return str_contains($teks, ',') ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }
}
