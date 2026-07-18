<?php

namespace App\Http\Controllers;

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

        $rawItems = $type === 'indoor' ? $order->detailItems() : $order->items()->with('hargaCetak')->get();

        $items = $rawItems->map(function ($item) use ($type) {
            if ($type === 'indoor') {
                $produk = Produk::where('KdProd', $item->KdProd)->first();
                $subtotal = $produk
                    ? $this->pricingService->lineTotalIndoor(
                        $produk, $item->Panjang, $item->Lebar, $item->Qty,
                        $item->PisauTurun, $item->JumlahKertas, $item->TebalKertas,
                    )
                    : 0;
                $name = $item->Judul;
            } else {
                $harga = $item->hargaCetak;
                $subtotal = $harga ? $this->pricingService->lineTotalOutdoor($harga, $item->Panjang, $item->Lebar, $item->Qty) : 0;
                $name = $item->NmFile;
            }

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
