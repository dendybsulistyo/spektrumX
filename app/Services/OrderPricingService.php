<?php

namespace App\Services;

use App\Models\HargaCetakOutdoor;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\Produk;

class OrderPricingService
{
    /**
     * Indoor Panjang/Lebar are entered in meters (per the order form labels);
     * no unit conversion is applied.
     *
     * isPjLb is cast to boolean on the model, but the underlying legacy column
     * actually stores codes 1-4 (never 0), so every row casts to true. Per
     * business confirmation, only the raw value 1 means "priced by area" —
     * read the raw (uncast) value here rather than $produk->isPjLb.
     */
    public function lineTotalIndoor(Produk $produk, float $panjang, float $lebar, int $qty): float
    {
        $isAreaBased = (int) $produk->getRawOriginal('isPjLb') === 1;

        return $isAreaBased
            ? $produk->HargaStd * $panjang * $lebar * $qty
            : $produk->HargaStd * $qty;
    }

    /**
     * Outdoor Panjang/Lebar are entered in centimeters (per the order form labels).
     * HargaStd is assumed to be a price per square meter — confirm against real
     * pricing once this feature is live, since hcetak_outdoor has no explicit flag.
     */
    public function lineTotalOutdoor(HargaCetakOutdoor $harga, float $panjangCm, float $lebarCm, int $qty): float
    {
        $areaM2 = ($panjangCm / 100) * ($lebarCm / 100);

        return $harga->HargaStd * $areaM2 * $qty;
    }

    public function totalIndoor(OrderIndoor $order): float
    {
        return $order->detailItems()->sum(function ($item) {
            $produk = Produk::where('KdProd', $item->KdProd)->first();

            return $produk ? $this->lineTotalIndoor($produk, $item->Panjang, $item->Lebar, $item->Qty) : 0;
        });
    }

    public function totalOutdoor(OrderOutdoor $order): float
    {
        return $order->items->sum(function ($item) {
            $harga = $item->hargaCetak;

            return $harga ? $this->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0;
        });
    }
}
