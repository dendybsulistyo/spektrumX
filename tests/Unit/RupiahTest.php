<?php

namespace Tests\Unit;

use App\Models\HargaArtwork;
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

    public function test_outdoor_minimum_one_square_meter_applies_per_nota(): void
    {
        $harga = (new HargaCetakOutdoor)->setRawAttributes([
            'KdCtk' => 'TEST',
            'HargaStd' => 10000,
        ]);
        $pricing = new OrderPricingService;
        $item = fn (float $p, float $l, int $qty) => (object) ['Panjang' => $p, 'Lebar' => $l, 'Qty' => $qty];

        // 90×90 + 80×80 = 1,45 m² → ukuran asli.
        $factor = $pricing->outdoorAreaFactor([$item(90, 90, 1), $item(80, 80, 1)]);
        $this->assertSame(1.0, $factor);
        $this->assertSame(8100.0, $pricing->lineTotalOutdoor($harga, 90, 90, 1, null, $factor));
        $this->assertSame(6400.0, $pricing->lineTotalOutdoor($harga, 80, 80, 1, null, $factor));

        // Banyak kecil, qty ikut dihitung: 41×41×56 = 9,41 m² → ukuran asli (tidak 1 m² per lembar).
        $this->assertSame(1.0, $pricing->outdoorAreaFactor([$item(41, 41, 56)]));
        $this->assertSame(94200.0, $pricing->lineTotalOutdoor($harga, 41, 41, 56));

        // 30×30 + 40×40 = 0,25 m² → total menjadi harga 1 m².
        $factor = $pricing->outdoorAreaFactor([$item(30, 30, 1), $item(40, 40, 1)]);
        $total = $pricing->lineTotalOutdoor($harga, 30, 30, 1, null, $factor)
            + $pricing->lineTotalOutdoor($harga, 40, 40, 1, null, $factor);
        $this->assertSame(10000.0, $total);
    }

    public function test_harga_satuan_desimal_tetap_tampil(): void
    {
        $this->assertSame('0,7', Rupiah::harga(0.7));
        $this->assertSame('3,55', Rupiah::harga(3.55));
        $this->assertSame('27,5', Rupiah::harga('27.5'));
        $this->assertSame('20.000', Rupiah::harga(20000));
        $this->assertSame('1.250,5', Rupiah::harga(1250.5));
        $this->assertSame('0', Rupiah::harga(null));
    }

    public function test_artwork_pxl_desimal_dengan_harga_desimal(): void
    {
        // 1301 E Flute (cm): Rp 0,7 per cm², minimum Rp 20.000, isPjLb 2 (P×L).
        $harga = (new HargaArtwork)->setRawAttributes([
            'KdProd' => '1301', 'HargaStd' => 0.7, 'HargaMin' => 20000, 'isPjLb' => 2, 'isHPilih' => 0,
        ]);
        $pricing = new OrderPricingService;

        // 150,5 × 200,5 × 0,7 = 21.122,675 → dibulatkan ke atas per Rp100.
        $this->assertSame(21200.0, $pricing->lineTotalArtwork($harga, 150.5, 200.5, 1));
        // Di bawah minimum → Rp 20.000.
        $this->assertSame(20000.0, $pricing->lineTotalArtwork($harga, 45.5, 120.25, 1));
        // Qty ikut dikalikan: 100,5 × 100,5 × 0,7 × 3 = 21.210,525 → 21.300.
        $this->assertSame(21300.0, $pricing->lineTotalArtwork($harga, 100.5, 100.5, 3));
    }
}
