<?php

namespace App\Http\Controllers;

use App\Models\HargaArtwork;
use App\Models\Produk;
use App\Services\OrderPricingService;
use App\Support\ResolvesOrderType;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer', 'kasir');

        // The "nota pengganti" (replacement invoice) feature only exists for
        // Order Outdoor — Indoor/Artwork models don't define this relation.
        if ($type === 'outdoor') {
            $order->load('replaces');
        }

        $rawItems = match ($type) {
            'indoor' => $order->detailItems(),
            'outdoor' => $order->items()->with('hargaCetak')->get(),
            'artwork' => $order->items,
            default => abort(404),
        };

        $items = $rawItems->map(function ($item) use ($type) {
            [$name, $subtotal] = match ($type) {
                'indoor' => (function () use ($item) {
                    $produk = Produk::where('KdProd', $item->KdProd)->first();

                    return [
                        $item->Judul,
                        $produk
                            ? $this->pricingService->lineTotalIndoor(
                                $produk, $item->Panjang, $item->Lebar, $item->Qty,
                                $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                            )
                            : 0,
                    ];
                })(),
                'outdoor' => (function () use ($item) {
                    $harga = $item->hargaCetak;

                    return [
                        $item->NmFile,
                        $harga ? $this->pricingService->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0,
                    ];
                })(),
                'artwork' => (function () use ($item) {
                    $harga = HargaArtwork::where('KdProd', $item->KdProd)->first();

                    return [
                        $item->Judul,
                        $harga ? $this->pricingService->lineTotalArtwork($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0,
                    ];
                })(),
            };

            return (object) [
                'name' => $name,
                'panjang' => $item->Panjang,
                'lebar' => $item->Lebar,
                'qty' => $item->Qty,
                'subtotal' => $subtotal,
            ];
        });

        return view('invoice.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }
}
