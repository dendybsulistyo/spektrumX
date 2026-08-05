<?php

namespace App\Services;

use App\Models\HargaArtwork;
use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Models\KonfigurasiJasaPotong;
use App\Models\KonfigurasiJasaPotongArtwork;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\Produk;

class OrderPricingService
{
    /**
     * Indoor Panjang/Lebar are entered in whatever unit the product's Satuan
     * implies (e.g. "sqcm" → centimeters, "sqm" → meters) — HargaStd is
     * priced per that same unit, so no conversion is applied here; the order
     * form labels the fields accordingly. Only isPjLb === Produk::PJLB_AREA
     * (2) is priced by area — see Produk::isAreaPriced().
     *
     * isPjLb === Produk::PJLB_QTY_ALT (4, "Jasa Potong") uses a completely
     * separate formula that bypasses HargaStd entirely:
     * Ongkos = ((PisauTurun × JumlahKertas × TebalKertas) / 10) + X, where X
     * is the shop-wide constant in konfigurasi_jasa_potong.
     *
     * HargaMin is a floor on the line total (not per unit), matching the
     * common "minimum order charge" convention for print jobs.
     */
    public function lineTotalIndoor(
        Produk $produk,
        float $panjang,
        float $lebar,
        int $qty,
        ?int $pisauTurun = null,
        ?int $jumlahKertas = null,
        ?int $tebalKertas = null,
    ): float {
        if ($produk->isPjLb === Produk::PJLB_QTY_ALT && $pisauTurun !== null && $jumlahKertas !== null && $tebalKertas !== null) {
            $raw = (($pisauTurun * $jumlahKertas * $tebalKertas) / 10) + KonfigurasiJasaPotong::current()->nilai_x;

            return max($raw, $produk->HargaMin);
        }

        $raw = $produk->isAreaPriced()
            ? $produk->HargaStd * $panjang * $lebar * $qty
            : $produk->HargaStd * $qty;

        return max($raw, $produk->HargaMin);
    }

    /**
     * Outdoor Panjang/Lebar are entered in centimeters (per the order form labels).
     * HargaStd is assumed to be a price per square meter — confirm against real
     * pricing once this feature is live, since harga_cetak_outdoor has no explicit flag.
     *
     * VIP customers can have a per-KdCtk price override in
     * harga_cetak_outdoor_khusus — checked first when $kdCust is given, and
     * falls back to the shop-wide standard ($harga) when no override exists
     * for that customer/KdCtk combo.
     */
    public function lineTotalOutdoor(HargaCetakOutdoor $harga, float $panjangCm, float $lebarCm, int $qty, ?string $kdCust = null): float
    {
        $areaM2 = ($panjangCm / 100) * ($lebarCm / 100);

        $hargaStd = $harga->HargaStd;

        if ($kdCust) {
            $khusus = HargaCetakOutdoorKhusus::where('KdCust', $kdCust)->where('KdCtk', $harga->KdCtk)->first();
            $hargaStd = $khusus->HargaStd ?? $hargaStd;
        }

        return $hargaStd * $areaM2 * $qty;
    }

    public function totalIndoor(OrderIndoor $order): float
    {
        return $order->detailItems()->sum(function ($item) {
            $produk = Produk::where('KdProd', $item->KdProd)->first();

            return $produk
                ? $this->lineTotalIndoor(
                    $produk, $item->Panjang, $item->Lebar, $item->Qty,
                    $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                )
                : 0;
        });
    }

    public function totalOutdoor(OrderOutdoor $order): float
    {
        return $order->items->sum(function ($item) use ($order) {
            $harga = $item->hargaCetak;

            return $harga ? $this->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty, $order->KdCust) : 0;
        });
    }

    /**
     * Artwork pricing follows the same isPjLb convention as Indoor (1 =
     * Qty×Harga, 2 = P×L×Qty×Harga, 4 = Jasa Potong). Jasa Potong Artwork
     * uses the same formula as Indoor's — ((PisauTurun × JumlahKertas ×
     * TebalKertas) / 10) + X — but X comes from its own
     * konfigurasi_jasa_potong_artwork row, independent of Indoor's.
     * HargaMin floors the line total, same convention.
     */
    public function lineTotalArtwork(
        HargaArtwork $harga,
        float $panjang,
        float $lebar,
        int $qty,
        ?int $pisauTurun = null,
        ?int $jumlahKertas = null,
        ?int $tebalKertas = null,
    ): float {
        if ($harga->isJasaPotong() && $pisauTurun !== null && $jumlahKertas !== null && $tebalKertas !== null) {
            $raw = (($pisauTurun * $jumlahKertas * $tebalKertas) / 10) + KonfigurasiJasaPotongArtwork::current()->nilai_x;

            return max($raw, $harga->HargaMin);
        }

        $raw = $harga->isAreaPriced()
            ? $harga->HargaStd * $panjang * $lebar * $qty
            : $harga->HargaStd * $qty;

        return max($raw, $harga->HargaMin);
    }

    public function totalArtwork(OrderArtwork $order): float
    {
        return $order->items->sum(function ($item) {
            $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();

            return $harga
                ? $this->lineTotalArtwork(
                    $harga, $item->Panjang, $item->Lebar, $item->Qty,
                    $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                )
                : 0;
        });
    }
}
