<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_it_spells_rupiah_amounts(int $amount, string $expected): void
    {
        $this->assertSame($expected, Terbilang::rupiah($amount));
    }

    public static function amounts(): array
    {
        return [
            [0, 'Nol rupiah'],
            [11, 'Sebelas rupiah'],
            [1000, 'Seribu rupiah'],
            [373126, 'Tiga ratus tujuh puluh tiga ribu seratus dua puluh enam rupiah'],
            [190500, 'Seratus sembilan puluh ribu lima ratus rupiah'],
        ];
    }
}
