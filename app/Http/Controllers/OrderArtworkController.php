<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderArtworkRequest;
use App\Models\Customer;
use App\Models\HargaArtwork;
use App\Models\OrderArtwork;
use App\Models\OrderArtworkDetail;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderArtworkController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function index(Request $request): View
    {
        $orders = OrderArtwork::query()
            ->with('customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NoOrder', 'like', "%{$search}%")
                    ->orWhere('KdCust', 'like', "%{$search}%");
            })
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(15)
            ->withQueryString();

        return view('order-artwork.index', compact('orders'));
    }

    public function create(): View
    {
        return view('order-artwork.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : null,
            'produkList' => HargaArtwork::orderBy('NoUrut')->get(),
        ]);
    }

    public function store(StoreOrderArtworkRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $noOrder = $this->generateNoOrder($data['TglOrder']);

            $order = OrderArtwork::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'Cetak' => false,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalArtwork($order->fresh('items'))]);
        });

        return redirect()->route('order-artwork.index')->with('status', 'Order artwork berhasil dibuat.');
    }

    public function edit(OrderArtwork $orderArtwork): View
    {
        return view('order-artwork.edit', [
            'order' => $orderArtwork,
            'items' => $orderArtwork->items,
            'selectedCustomer' => $orderArtwork->customer,
            'produkList' => HargaArtwork::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderArtworkRequest $request, OrderArtwork $orderArtwork): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderArtwork) {
            $orderArtwork->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
            ]);

            $orderArtwork->items()->delete();

            $this->saveItems($orderArtwork, $data['items']);

            $orderArtwork->update(['total' => $this->pricingService->totalArtwork($orderArtwork->fresh('items'))]);
        });

        return redirect()->route('order-artwork.index')->with('status', 'Order artwork berhasil diperbarui.');
    }

    public function destroy(OrderArtwork $orderArtwork): RedirectResponse
    {
        $orderArtwork->delete();

        return redirect()->route('order-artwork.index')->with('status', 'Order artwork berhasil dihapus.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderArtwork $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            $produk = HargaArtwork::where('KdProd', $item['KdProd'])->first();

            OrderArtworkDetail::create([
                'order_artwork_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'KdProd' => $item['KdProd'],
                'NmProd' => $produk->NmProd ?? '',
                'Judul' => $item['Judul'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
            ]);
        }
    }

    private function generateNoOrder(string $tglOrder): string
    {
        $prefix = 'A'.date('ymd', strtotime($tglOrder));

        $last = OrderArtwork::where('NoOrder', 'like', $prefix.'%')
            ->orderByDesc('NoOrder')
            ->value('NoOrder');

        $nextSeq = $last ? ((int) substr($last, 7, 5)) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
