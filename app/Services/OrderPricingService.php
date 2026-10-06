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
use App\Support\Rupiah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderPricingService
{
    private ?Collection $printerNames = null;

    private ?Collection $bahanNames = null;

    private array $produkCache = [];

    private array $artworkCache = [];

    private array $outdoorSpecialPriceCache = [];

    private ?float $cuttingValue = null;

    private ?float $artworkCuttingValue = null;

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

            return Rupiah::bulatkan(max($raw, $produk->HargaMin));
        }

        $raw = $produk->isAreaPriced()
            ? $produk->HargaStd * $panjang * $lebar * $qty
            : $produk->HargaStd * $qty;

        return Rupiah::bulatkan(max($raw, $produk->HargaMin));
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

        $hargaStd = (float) $harga->HargaStd;

        if ($kdCust) {
            $cacheKey = $kdCust.'|'.$harga->KdCtk;
            if (! array_key_exists($cacheKey, $this->outdoorSpecialPriceCache)) {
                $this->outdoorSpecialPriceCache[$cacheKey] = HargaCetakOutdoorKhusus::query()
                    ->where('KdCust', $kdCust)
                    ->where('KdCtk', $harga->KdCtk)
                    ->value('HargaStd');
            }
            $hargaStd = array_key_exists($cacheKey, $this->outdoorSpecialPriceCache)
                && $this->outdoorSpecialPriceCache[$cacheKey] !== null
                    ? (float) $this->outdoorSpecialPriceCache[$cacheKey]
                    : $hargaStd;
        }

        // Area-based pricing (harga per m²) almost never lands on a round
        // Rupiah amount once Panjang/Lebar aren't whole meters — always
        // round up to Rp100 so subtotal/total never show a sub-100 remainder.
        return Rupiah::bulatkan($hargaStd * $areaM2 * $qty);
    }

    /**
     * Order Indoor now also accepts Artwork-catalog items in the same
     * order/nota (one nota instead of two when a customer orders both) —
     * each line's `jenis_produk` says which catalog/formula to use.
     */
    public function totalIndoor(OrderIndoor $order): float
    {
        return Rupiah::bulatkan($order->detailItems()->sum(function ($item) {
            if ($this->usesCashierUnitPrice($item)) {
                return Rupiah::bulatkan((float) $item->harga_satuan_kasir * (int) $item->Qty);
            }

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
        }));
    }

    public function totalOutdoor(OrderOutdoor $order): float
    {
        return Rupiah::bulatkan($order->items->sum(function ($item) use ($order) {
            $harga = $item->hargaCetak;

            return $harga ? $this->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty, $order->KdCust) : 0;
        }));
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

            return Rupiah::bulatkan(max($raw, $harga->HargaMin));
        }

        $raw = $harga->isAreaPriced()
            ? $harga->HargaStd * $panjang * $lebar * $qty
            : $harga->HargaStd * $qty;

        return Rupiah::bulatkan(max($raw, $harga->HargaMin));
    }

    public function totalArtwork(OrderArtwork $order): float
    {
        return Rupiah::bulatkan($order->items->sum(function ($item) {
            $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();

            if ($this->usesCashierUnitPrice($item)) {
                return Rupiah::bulatkan((float) $item->harga_satuan_kasir * (int) $item->Qty);
            }

            return $harga
                ? $this->lineTotalArtwork(
                    $harga, $item->Panjang, $item->Lebar, $item->Qty,
                    $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                )
                : 0;
        }));
    }

    /**
     * Per-line "asal angka" breakdown — name, dimensions, unit price,
     * subtotal, the underlying "bahan" (Outdoor: bahan cetak; Indoor/
     * Artwork: the catalog NmProd/KdProd actually used, separate from
     * Judul which is just the kasir's free-text job title), and a note for
     * the cases Harga Satuan alone can't explain (Outdoor's printer, or the
     * Jasa Potong formula which bypasses HargaStd entirely) — shared by the
     * printed Surat Pesanan and the Kasir payment page so both always show
     * the exact same numbers.
     *
     * @param  Collection<int, mixed>  $rawItems
     * @return Collection<int, object{name: string, bahan: ?string, printer: ?string, panjang: mixed, lebar: mixed, qty: mixed, harga_satuan: ?float, subtotal: float, breakdown: ?string}>
     */
    public function detailedLineItems(string $type, OrderIndoor|OrderOutdoor|OrderArtwork $order, Collection $rawItems, bool $live = false): Collection
    {
        $printerNames = $type === 'outdoor'
            ? ($this->printerNames ??= PrinterOutdoor::pluck('NmPrn', 'KdPrn'))
            : collect();
        $bahanNames = $type === 'outdoor'
            ? ($this->bahanNames ??= BahanCetakOutdoor::pluck('NmBhn', 'NoCetak'))
            : collect();

        $lines = $rawItems->map(function ($item) use ($type, $order, $printerNames, $bahanNames, $live) {
            [$name, $subtotal, $hargaSatuan, $bahan, $printer, $breakdown] = match ($type) {
                // Order Indoor now also holds Artwork-catalog items in the
                // same order (jenis_produk per line) — look up whichever
                // catalog/formula that specific line was priced from.
                'indoor' => (function () use ($item) {
                    if ($this->usesCashierUnitPrice($item)) {
                        $unitPrice = (float) $item->harga_satuan_kasir;
                        $isArtwork = $item->isArtwork();
                        $catalog = $isArtwork ? $this->artwork($item->KdProd, true) : $this->produk($item->KdProd);

                        return [
                            $item->Judul,
                            Rupiah::bulatkan($unitPrice * (int) $item->Qty),
                            $unitPrice,
                            $catalog?->kategori?->NmDivs,
                            $this->produkNama($item),
                            'Harga diinput Kasir',
                        ];
                    }

                    if ($item->isArtwork()) {
                        $harga = $this->artwork($item->KdProd, true);
                        $nilaiX = $harga?->isJasaPotong() ? $this->artworkCuttingValue() : null;

                        return [
                            $item->Judul,
                            $harga
                                ? $this->lineTotalArtwork(
                                    $harga, $item->Panjang, $item->Lebar, $item->Qty,
                                    $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                                )
                                : 0,
                            $harga && $nilaiX === null ? $harga->HargaStd : null,
                            $harga?->kategori?->NmDivs,
                            $this->produkNama($item),
                            $harga ? $this->produkBreakdown($item, $nilaiX) : null,
                        ];
                    }

                    $produk = $this->produk($item->KdProd);
                    $nilaiX = $produk?->isPjLb === Produk::PJLB_QTY_ALT ? $this->cuttingValue() : null;

                    return [
                        $item->Judul,
                        $produk
                            ? $this->lineTotalIndoor(
                                $produk, $item->Panjang, $item->Lebar, $item->Qty,
                                $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                            )
                            : 0,
                        $produk && $nilaiX === null ? $produk->HargaStd : null,
                        $produk?->kategori?->NmDivs,
                        $this->produkNama($item),
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
                    $bahan = null;
                    $printer = null;
                    if ($harga) {
                        $areaM2 = ((float) $item->Panjang / 100) * ((float) $item->Lebar / 100);
                        $printer = $printerNames[$item->printerCode()] ?? $item->printerCode() ?? '-';
                        $bahan = $bahanNames[$item->bahanCode()] ?? $item->bahanCode() ?? '-';
                        // Back-derived from the subtotal (rather than
                        // re-reading harga_cetak_outdoor directly) so it
                        // always matches whatever price actually applied,
                        // VIP override included.
                        $hargaSatuan = $areaM2 * $item->Qty > 0 ? $subtotal / ($areaM2 * $item->Qty) : $harga->HargaStd;
                    }

                    // No breakdown text — same treatment as Indoor: Bahan
                    // and Printer are their own columns, and Panjang/Lebar/
                    // Qty/Harga Satuan already fully explain the subtotal.
                    return [$item->NmFile, $subtotal, $hargaSatuan, $bahan, $printer, null];
                })(),
                'artwork' => (function () use ($item) {
                    $harga = $this->artwork($item->KdProd);
                    $nilaiX = $harga?->isJasaPotong() ? $this->artworkCuttingValue() : null;

                    if ($this->usesCashierUnitPrice($item)) {
                        $unitPrice = (float) $item->harga_satuan_kasir;

                        return [$item->Judul, Rupiah::bulatkan($unitPrice * (int) $item->Qty), $unitPrice, $this->produkNama($item), null, 'Harga Artwork diinput Kasir'];
                    }

                    return [
                        $item->Judul,
                        $harga ? $this->lineTotalArtwork($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0,
                        $harga && $nilaiX === null ? $harga->HargaStd : null,
                        $this->produkNama($item),
                        null,
                        $harga ? $this->produkBreakdown($item, $nilaiX) : null,
                    ];
                })(),
            };

            // The price list can change after an order is charged. Show the
            // price that was snapshotted when the order total was computed.
            if (! $live && $item->subtotal_snapshot !== null) {
                $subtotal = (float) $item->subtotal_snapshot;
                $hargaSatuan = $item->harga_satuan_snapshot !== null ? (float) $item->harga_satuan_snapshot : null;
            }

            return (object) [
                'name' => $name,
                'bahan' => $bahan,
                'printer' => $printer,
                'panjang' => $item->Panjang,
                'lebar' => $item->Lebar,
                'qty' => $item->Qty,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $subtotal,
                'breakdown' => $breakdown,
                'detail_id' => $item->id,
                'kd_prod' => (string) $item->KdProd,
                'harga_satuan_kasir' => $item->harga_satuan_kasir !== null ? (float) $item->harga_satuan_kasir : null,
                'harga_kasir_dapat_diisi' => $this->cashierUnitPriceAllowed($item),
            ];
        });

        return $live ? $lines : $this->reconcileWithOrderTotal($order, $rawItems, $lines);
    }

    /**
     * Store the live per-line price on each detail row. Call this wherever
     * the order total is (re)computed, so the nota keeps showing the price
     * that was charged even after the price list changes.
     */
    public function snapshotLinePrices(string $type, OrderIndoor|OrderOutdoor|OrderArtwork $order): void
    {
        $order->unsetRelation('items');
        $rawItems = $type === 'outdoor' ? $order->items()->with('hargaCetak')->get() : $order->detailItems();
        $lines = $this->detailedLineItems($type, $order, $rawItems, live: true);
        $table = $rawItems->first()?->getTable();

        foreach ($lines as $line) {
            DB::table($table)->where('id', $line->detail_id)->update([
                'harga_satuan_snapshot' => $line->harga_satuan !== null ? round((float) $line->harga_satuan, 2) : null,
                'subtotal_snapshot' => round((float) $line->subtotal, 2),
            ]);
        }
    }

    /**
     * Orders saved before price snapshots existed are re-priced from today's
     * price list, so their lines may not add up to the total that was
     * actually charged (and journaled). Scale those lines so the nota and
     * reports always agree with order.total. Differences below Rp100 are the
     * normal rounding of the total and are left alone.
     */
    private function reconcileWithOrderTotal(OrderIndoor|OrderOutdoor|OrderArtwork $order, Collection $rawItems, Collection $lines): Collection
    {
        $target = (float) $order->total;
        $sum = (float) $lines->sum('subtotal');

        if ($lines->isEmpty() || $target <= 0 || $sum <= 0
            || abs($target - $sum) < Rupiah::UNIT_PEMBULATAN
            || $rawItems->count() !== $order->detailItems()->count()) {
            return $lines;
        }

        $factor = $target / $sum;
        $allocated = 0.0;
        $lastIndex = $lines->keys()->last();

        return $lines->map(function ($line, $index) use ($factor, $target, &$allocated, $lastIndex) {
            $original = (float) $line->subtotal;
            $line->subtotal = $index === $lastIndex ? $target - $allocated : round($original * $factor);
            $allocated += $line->subtotal;
            if ($line->harga_satuan !== null && $original > 0) {
                $line->harga_satuan = round((float) $line->harga_satuan * $line->subtotal / $original, 2);
            }
            $line->breakdown = null;

            return $line;
        });
    }

    private function usesCashierUnitPrice($item): bool
    {
        return $this->cashierUnitPriceAllowed($item)
            && $item->harga_satuan_kasir !== null
            && (float) $item->harga_satuan_kasir > 0;
    }

    private function cashierUnitPriceAllowed($item): bool
    {
        $isArtworkCatalog = $item instanceof \App\Models\OrderArtworkDetail
            || (method_exists($item, 'isArtwork') && $item->isArtwork());

        if ($isArtworkCatalog) {
            $artwork = $this->artwork((string) $item->KdProd);

            return $artwork === null || (float) $artwork->HargaStd <= 0;
        }

        return (string) $item->KdProd === '2001';
    }

    /**
     * The Indoor/Artwork catalog product actually used to fulfill this
     * line (NmProd/KdProd), separate from Judul which is just the kasir's
     * free-text job title. For a merged Order Indoor row this fills the
     * "Printer" column slot (there's no real printer for Indoor/Artwork,
     * so that slot is repurposed to show which product was picked) — the
     * "Bahan" slot instead shows the product's Kategori for those rows.
     */
    private function produkNama($item): ?string
    {
        return $item->NmProd ? "{$item->NmProd} ({$item->KdProd})" : null;
    }

    /**
     * Renders the "asal angka" breakdown note for an Indoor/Artwork line —
     * only the Jasa Potong formula needs it (isPjLb 4 bypasses HargaStd
     * entirely, so the Harga Satuan column is blank for it and has nothing
     * else to explain how the subtotal was computed). Everything else is
     * already fully explained by the Harga Satuan/Qty/Subtotal columns.
     */
    private function produkBreakdown($item, ?float $nilaiX): ?string
    {
        if ($nilaiX === null) {
            return null;
        }

        return 'Jasa Potong: (PisauTurun '.$item->PisauTurun.' × JumlahKertas '.$item->JumlahKertas.' × TebalKertas '.$item->TebalKertas.') ÷ 10 + Rp '.number_format($nilaiX, 0, ',', '.');
    }

    private function produk(?string $code): ?Produk
    {
        $key = (string) $code;
        if (! array_key_exists($key, $this->produkCache)) {
            $this->produkCache[$key] = Produk::query()->where('KdProd', $key)->with('kategori')->first();
        }

        return $this->produkCache[$key];
    }

    private function artwork(?string $code, bool $withCategory = false): ?HargaArtwork
    {
        $key = (string) $code;
        if (! array_key_exists($key, $this->artworkCache)) {
            $query = HargaArtwork::query()->where('KdProd', $key);
            $this->artworkCache[$key] = $withCategory ? $query->with('kategori')->first() : $query->first();
        } elseif ($withCategory && $this->artworkCache[$key] && ! $this->artworkCache[$key]->relationLoaded('kategori')) {
            $this->artworkCache[$key]->load('kategori');
        }

        return $this->artworkCache[$key];
    }

    private function cuttingValue(): float
    {
        return $this->cuttingValue ??= (float) KonfigurasiJasaPotong::current()->nilai_x;
    }

    private function artworkCuttingValue(): float
    {
        return $this->artworkCuttingValue ??= (float) KonfigurasiJasaPotongArtwork::current()->nilai_x;
    }
}
