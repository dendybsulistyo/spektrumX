<?php

namespace Tests\Unit;

use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Support\Rupiah;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    #[DataProvider('outdoorPrices')]
    public function test_it_normalizes_legacy_and_full_outdoor_prices(float $stored, float $expected): void
    {
        $this->assertSame($expected, Rupiah::hargaOutdoor($stored));
    }

    public static function outdoorPrices(): array
    {
        return [
            'legacy decimal' => [23.5, 23500.0],
            'legacy whole' => [40, 40000.0],
            'full rupiah' => [48500, 48500.0],
            'empty' => [0, 0.0],
        ];
    }

    public function test_outdoor_models_keep_master_values_unchanged(): void
    {
        $standard = (new HargaCetakOutdoor)->setRawAttributes(['HargaStd' => 23.5, 'HargaMin' => 40]);
        $special = (new HargaCetakOutdoorKhusus)->setRawAttributes(['HargaStd' => 48.5]);

        $this->assertSame(23.5, $standard->HargaStd);
        $this->assertSame(40.0, $standard->HargaMin);
        $this->assertSame(48.5, $special->HargaStd);
    }
}
