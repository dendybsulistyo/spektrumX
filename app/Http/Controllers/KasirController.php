<?php

namespace App\Http\Controllers;

use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Services\CustomerCreditService;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KasirController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly CustomerCreditService $creditService) {}

    public function index(): View
    {
        $indoorOrders = OrderIndoor::query()
            ->with('customer')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        $outdoorOrders = OrderOutdoor::query()
            ->with('customer')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        return view('kasir.index', compact('indoorOrders', 'outdoorOrders'));
    }

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer.limit');

        $items = $type === 'indoor' ? $order->detailItems() : $order->items;

        return view('kasir.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }

    public function bayar(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        $data = $request->validate([
            'metode_bayar' => ['required', 'in:tunai,hutang'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->load('customer.limit');
        $total = (float) $order->total;

        if ($data['metode_bayar'] === 'hutang') {
            if (! $this->creditService->canTakeHutang($order->customer, $total)) {
                return back()->with('error', 'Customer tidak bisa hutang: bukan VIP atau melebihi sisa limit piutang.');
            }
        }

        DB::transaction(function () use ($order, $type, $data, $total) {
            if ($data['metode_bayar'] === 'hutang') {
                $this->creditService->addHutang($order->customer, $total);

                $order->update([
                    'status_bayar' => 'hutang',
                    'metode_bayar' => 'hutang',
                    'jumlah_dibayar' => 0,
                    'jumlah_piutang' => $total,
                ]);
            } else {
                $order->update([
                    'status_bayar' => 'lunas',
                    'metode_bayar' => 'tunai',
                    'jumlah_dibayar' => $total,
                    'jumlah_piutang' => 0,
                ]);
            }

            $order->update([
                'kasir_user_id' => auth()->id(),
                'dibayar_at' => now(),
                'status' => 'desain',
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => $data['catatan'] ?? null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('kasir.index')->with('status', 'Pembayaran berhasil diproses.');
    }
}
