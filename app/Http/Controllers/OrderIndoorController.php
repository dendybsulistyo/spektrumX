<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderIndoorRequest;
use App\Models\Customer;
use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use App\Models\Operator;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderIndoorController extends Controller
{
    public function index(Request $request): View
    {
        $orders = OrderIndoor::query()
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

        return view('order-indoor.index', compact('orders'));
    }

    public function create(): View
    {
        return view('order-indoor.create', [
            'customers' => Customer::orderBy('NmCust')->get(),
            'operators' => Operator::orderBy('NmOpr')->get(),
            'produkList' => Produk::orderBy('NoUrut')->get(),
        ]);
    }

    public function store(StoreOrderIndoorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $noOrder = $this->generateNoOrder($data['TglOrder']);

            $order = OrderIndoor::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'KdOpr' => $data['KdOpr'],
                'Cetak' => 0,
            ]);

            $this->saveItems($order, $data['items']);
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil dibuat.');
    }

    public function edit(OrderIndoor $orderIndoor): View
    {
        $items = OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->get();

        return view('order-indoor.edit', [
            'order' => $orderIndoor,
            'items' => $items,
            'customers' => Customer::orderBy('NmCust')->get(),
            'operators' => Operator::orderBy('NmOpr')->get(),
            'produkList' => Produk::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderIndoorRequest $request, OrderIndoor $orderIndoor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderIndoor) {
            $orderIndoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
                'KdOpr' => $data['KdOpr'],
            ]);

            OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->delete();

            $this->saveItems($orderIndoor, $data['items']);
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil diperbarui.');
    }

    public function destroy(OrderIndoor $orderIndoor): RedirectResponse
    {
        DB::transaction(function () use ($orderIndoor) {
            OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->delete();
            $orderIndoor->delete();
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil dihapus.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderIndoor $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            $produk = Produk::where('KdProd', $item['KdProd'])->first();

            OrderIndoorDetail::create([
                'BrsOrder' => $brsOrder,
                'KdProd' => $item['KdProd'],
                'NmProd' => $produk->NmProd ?? '',
                'Judul' => $item['Judul'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'KdStat' => 0,
            ]);
        }
    }

    private function generateNoOrder(string $tglOrder): string
    {
        $prefix = date('ymd', strtotime($tglOrder));

        $last = OrderIndoor::where('NoOrder', 'like', $prefix.'%')
            ->orderByDesc('NoOrder')
            ->value('NoOrder');

        $nextSeq = $last ? ((int) substr($last, 6, 5)) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
