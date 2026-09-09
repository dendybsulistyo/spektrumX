<?php

namespace App\Support;

class Terbilang
{
    /** @var array<int, string> */
    private const DIGITS = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    public static function rupiah(int|float|string|null $amount): string
    {
        $value = (int) round((float) $amount);

        if ($value === 0) {
            return 'Nol rupiah';
        }

        $words = self::spell(abs($value));

        return ucfirst(($value < 0 ? 'minus ' : '').$words).' rupiah';
    }

    private static function spell(int $value): string
    {
        if ($value < 12) {
            return self::DIGITS[$value];
        }

        if ($value < 20) {
            return self::spell($value - 10).' belas';
        }

        if ($value < 100) {
            return trim(self::spell(intdiv($value, 10)).' puluh '.self::spell($value % 10));
        }

        if ($value < 200) {
            return trim('seratus '.self::spell($value - 100));
        }

        if ($value < 1000) {
            return trim(self::spell(intdiv($value, 100)).' ratus '.self::spell($value % 100));
        }

        if ($value < 2000) {
            return trim('seribu '.self::spell($value - 1000));
        }

        if ($value < 1_000_000) {
            return trim(self::spell(intdiv($value, 1000)).' ribu '.self::spell($value % 1000));
        }

        if ($value < 1_000_000_000) {
            return trim(self::spell(intdiv($value, 1_000_000)).' juta '.self::spell($value % 1_000_000));
        }

        if ($value < 1_000_000_000_000) {
            return trim(self::spell(intdiv($value, 1_000_000_000)).' miliar '.self::spell($value % 1_000_000_000));
        }

        return trim(self::spell(intdiv($value, 1_000_000_000_000)).' triliun '.self::spell($value % 1_000_000_000_000));
    }
}
