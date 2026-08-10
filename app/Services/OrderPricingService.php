<?php

namespace App\Services;

use App\Models\BahanCetakOutdoor;
use App\Models\HargaArtwork;
use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Models\KonfigurasiJasaPotong;
use App\Models\KonfigurasiJasaPotongArtwork;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\PrinterOutdoor;
use App\Models\Produk;
use Illuminate\Support\Collection;

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

    /**
     * Order Indoor now also accepts Artwork-catalog items in the same
     * order/nota (one nota instead of two when a customer orders both) —
     * each line's `jenis_produk` says which catalog/formula to use.
     */
    public function totalIndoor(OrderIndoor $order): float
    {
        return $order->detailItems()->sum(function ($item) {
            if ($item->isArtwork()) {
                $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();

                return $harga
                    ? $this->lineTotalArtwork(
                        $harga, $item->Panjang, $item->Lebar, $item->Qty,
                        $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                    )
                    : 0;
            }

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

    /**
     * Per-line "asal angka" breakdown — name, dimensions, unit price,
     * subtotal, and a human-readable note (printer/bahan for Outdoor, the
     * Jasa Potong formula when no single unit price applies) — shared by
     * the printed Surat Pesanan and the Kasir payment page so both always
     * show the exact same numbers.
     *
     * @param  Collection<int, mixed>  $rawItems
     * @return Collection<int, object{name: string, panjang: mixed, lebar: mixed, qty: mixed, harga_satuan: ?float, subtotal: float, breakdown: ?string}>
     */
    public function detailedLineItems(string $type, OrderIndoor|OrderOutdoor|OrderArtwork $order, Collection $rawItems): Collection
    {
        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');
        $bahanNames = BahanCetakOutdoor::pluck('NmBhn', 'NoCetak');

        return $rawItems->map(function ($item) use ($type, $order, $printerNames, $bahanNames) {
            [$name, $subtotal, $hargaSatuan, $breakdown] = match ($type) {
                // Order Indoor now also holds Artwork-catalog items in the
                // same order (jenis_produk per line) — look up whichever
                // catalog/formula that specific line was priced from.
                'indoor' => (function () use ($item) {
                    if ($item->isArtwork()) {
                        $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();
                        $nilaiX = $harga?->isJasaPotong() ? KonfigurasiJasaPotongArtwork::current()->nilai_x : null;

                        return [
                            $item->Judul,
                            $harga
                                ? $this->lineTotalArtwork(
                                    $harga, $item->Panjang, $item->Lebar, $item->Qty,
                                    $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                                )
                                : 0,
                            $harga && $nilaiX === null ? $harga->HargaStd : null,
                            $harga ? $this->produkBreakdown($item, $nilaiX) : null,
                        ];
                    }

                    $produk = Produk::where('KdProd', $item->KdProd)->first();
                    $nilaiX = $produk?->isPjLb === Produk::PJLB_QTY_ALT ? KonfigurasiJasaPotong::current()->nilai_x : null;

                    return [
                        $item->Judul,
                        $produk
                            ? $this->lineTotalIndoor(
                                $produk, $item->Panjang, $item->Lebar, $item->Qty,
                                $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                            )
                            : 0,
                        $produk && $nilaiX === null ? $produk->HargaStd : null,
                        $produk ? $this->produkBreakdown($item, $nilaiX) : null,
                    ];
                })(),
                'outdoor' => (function () use ($item, $order, $printerNames, $bahanNames) {
                    $harga = $item->hargaCetak;

                    // Mirrors the VIP per-customer override the order form
                    // itself applied at creation time, so a VIP customer's
                    // nota shows the same unit price they were actually
                    // charged instead of the shop-wide standard.
                    $subtotal = $harga ? $this->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty, $order->KdCust) : 0;

                    $hargaSatuan = null;
                    $breakdown = null;
                    if ($harga) {
                        $areaM2 = ((float) $item->Panjang / 100) * ((float) $item->Lebar / 100);
                        $printer = $printerNames[$item->printerCode()] ?? $item->printerCode() ?? '-';
                        $bahan = $bahanNames[$item->bahanCode()] ?? $item->bahanCode() ?? '-';
                        // Back-derived from the subtotal (rather than
                        // re-reading harga_cetak_outdoor directly) so it
                        // always matches whatever price actually applied,
                        // VIP override included.
                        $hargaSatuan = $areaM2 * $item->Qty > 0 ? $subtotal / ($areaM2 * $item->Qty) : $harga->HargaStd;
                        $breakdown = "Printer: {$printer} · Bahan: {$bahan} · Luas ".number_format($areaM2, 2, ',', '.')." m²";
                    }

                    return [$item->NmFile, $subtotal, $hargaSatuan, $breakdown];
                })(),
                'artwork' => (function () use ($item) {
                    $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();
                    $nilaiX = $harga?->isJasaPotong() ? KonfigurasiJasaPotongArtwork::current()->nilai_x : null;

                    return [
                        $item->Judul,
                        $harga ? $this->lineTotalArtwork($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0,
                        $harga && $nilaiX === null ? $harga->HargaStd : null,
                        $harga ? $this->produkBreakdown($item, $nilaiX) : null,
                    ];
                })(),
            };

            return (object) [
                'name' => $name,
                'panjang' => $item->Panjang,
                'lebar' => $item->Lebar,
                'qty' => $item->Qty,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $subtotal,
                'breakdown' => $breakdown,
            ];
        });
    }

    /**
     * Renders the "asal angka" breakdown note for a Jasa Potong line (isPjLb
     * 4) — the only case that needs extra text since Harga Satuan has no
     * single unit price to show for it (the formula bypasses HargaStd
     * entirely).
     */
    private function produkBreakdown($item, ?float $nilaiX): ?string
    {
        if ($nilaiX === null) {
            return null;
        }

        return 'Jasa Potong: (PisauTurun '.$item->PisauTurun.' × JumlahKertas '.$item->JumlahKertas.' × TebalKertas '.$item->TebalKertas.') ÷ 10 + Rp '.number_format($nilaiX, 0, ',', '.');
    }
}
