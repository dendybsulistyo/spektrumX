<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Tenggat order dalam hari kerja: Minggu Spektrum libur, jadi tidak dihitung.
 * Order Sabtu 10.00 + 1 hari = Senin 10.00; order yang masuk hari Minggu mulai dihitung Senin 00.00.
 * Rumus yang sama dipakai versi SQL (sql()) untuk hitungan order telat di dashboard.
 */
final class HariKerja
{
    public static function tambah(CarbonInterface $start, int $days): CarbonInterface
    {
        $start = $start->copy();
        if ($start->isSunday()) {
            $start = $start->addDay()->startOfDay();
        }

        $days = max(0, $days);
        $remainder = $days % 6;
        $offset = intdiv($days, 6) * 7 + $remainder + ($start->dayOfWeekIso - 1 + $remainder >= 6 ? 1 : 0);

        return $start->addDays($offset);
    }

    /**
     * Ekspresi MySQL setara tambah(): $start kolom datetime, $days ekspresi jumlah hari.
     */
    public static function sql(string $start, string $days): string
    {
        $base = "(CASE WHEN WEEKDAY({$start}) = 6 THEN DATE({$start}) + INTERVAL 1 DAY ELSE {$start} END)";

        return "DATE_ADD({$base}, INTERVAL (FLOOR(({$days}) / 6) * 7 + MOD(({$days}), 6)"
            ." + CASE WHEN WEEKDAY({$base}) + MOD(({$days}), 6) >= 6 THEN 1 ELSE 0 END) DAY)";
    }
}
