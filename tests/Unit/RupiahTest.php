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

    public function test_outdoor_minimum_one_square_meter_applies_per_item(): void
    {
        $harga = (new HargaCetakOutdoor)->setRawAttributes([
            'KdCtk' => 'TEST',
            'HargaStd' => 55000,
        ]);
        $pricing = new OrderPricingService;
        $item = fn (float $p, float $l, int $qty) => (object) ['Panjang' => $p, 'Lebar' => $l, 'Qty' => $qty];
        $line = fn (float $p, float $l, int $qty) => $pricing->lineTotalOutdoor($harga, $p, $l, $qty, null, $pricing->outdoorAreaFactor($item($p, $l, $qty)));

        // 60×160 = 0,96 m² → ditagih 1 m², walau nota berisi item besar lain (kasus OUT.1.261107021).
        $this->assertSame(55000.0, $line(60, 160, 1));
        $this->assertSame(110000.0, $line(100, 200, 1));

        // Qty ikut dihitung: 100×35×22 = 7,7 m² → ukuran asli.
        $this->assertSame(1.0, $pricing->outdoorAreaFactor($item(100, 35, 22)));
        $this->assertSame(423500.0, $line(100, 35, 22));
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
