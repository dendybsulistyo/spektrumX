<?php

namespace Tests\Unit;

use App\Support\HariKerja;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HariKerjaTest extends TestCase
{
    /** @return array<string, array{string, int, string}> */
    public static function tenggat(): array
    {
        return [
            'Senin + 1' => ['2026-11-09 10:00', 1, '2026-11-10 10:00'],
            'Jumat + 1' => ['2026-11-13 10:00', 1, '2026-11-14 10:00'],
            'Sabtu + 1 lewati Minggu' => ['2026-11-14 10:00', 1, '2026-11-16 10:00'],
            'Jumat + 2 lewati Minggu' => ['2026-11-13 10:00', 2, '2026-11-16 10:00'],
            'Sabtu + 6 = satu minggu kerja' => ['2026-11-14 10:00', 6, '2026-11-21 10:00'],
            'Rabu + 7' => ['2026-11-11 08:00', 7, '2026-11-19 08:00'],
            'masuk Minggu mulai Senin 00.00' => ['2026-11-15 14:00', 1, '2026-11-17 00:00'],
        ];
    }

    #[DataProvider('tenggat')]
    public function test_minggu_tidak_dihitung(string $start, int $days, string $expected): void
    {
        $this->assertSame($expected, HariKerja::tambah(Carbon::parse($start), $days)->format('Y-m-d H:i'));
    }
}
