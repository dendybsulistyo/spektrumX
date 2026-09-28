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
     * Legacy Outdoor prices were stored in thousands (23.5 = Rp23.500),
     * while newer rows store the full Rupiah amount. Accept both formats.
     */
    public static function hargaOutdoor(float|int|string|null $nominal): float
    {
        $value = (float) ($nominal ?? 0);

        return $value > 0 && $value < 1000 ? $value * 1000 : $value;
    }

    /** Convert an Indonesian whole-Rupiah input (135.000) into raw digits. */
    public static function dariInput(float|int|string|null $nominal): float|int|string|null
    {
        if (! is_string($nominal)) {
            return $nominal;
        }

        $digits = preg_replace('/\D/', '', $nominal);

        return $digits === '' ? null : $digits;
    }
}
