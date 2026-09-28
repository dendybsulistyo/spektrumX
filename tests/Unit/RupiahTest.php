<?php

namespace Tests\Unit;

use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
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
}
