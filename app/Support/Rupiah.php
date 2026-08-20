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
}
