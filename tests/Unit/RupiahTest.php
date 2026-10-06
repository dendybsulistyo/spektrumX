<?php

namespace Tests\Unit;

use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Services\OrderPricingService;
use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_outdoor_models_keep_master_values_unchanged(): void
    {
        $standard = (new HargaCetakOutdoor)->setRawAttributes(['HargaStd' => 23500, 'HargaMin' => 40000]);
        $special = (new HargaCetakOutdoorKhusus)->setRawAttributes(['HargaStd' => 48500]);

        $this->assertSame(23500, $standard->HargaStd);
        $this->assertSame(40000, $standard->HargaMin);
        $this->assertSame(48500, $special->HargaStd);
    }

    public function test_outdoor_price_uses_one_square_meter_minimum_per_quantity(): void
    {
        $harga = (new HargaCetakOutdoor)->setRawAttributes([
            'KdCtk' => 'TEST',
            'HargaStd' => 13800,
        ]);
        $pricing = new OrderPricingService;

        $this->assertSame(27600.0, $pricing->lineTotalOutdoor($harga, 50, 50, 2));
        $this->assertSame([100.0, 100.0], $pricing->outdoorBillableDimensions(80, 120));
        $this->assertSame(20700.0, $pricing->lineTotalOutdoor($harga, 50, 300, 1));
        $this->assertSame([50.0, 300.0], $pricing->outdoorBillableDimensions(50, 300));
    }
}
