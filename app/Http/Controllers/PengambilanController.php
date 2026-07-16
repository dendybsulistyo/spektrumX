<?php

namespace App\Http\Controllers;

use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengambilanController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        return view('pengambilan.index', compact('indoorOrders', 'outdoorOrders'));
    }

    public function serahkan(string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        $order->update([
            'status' => 'selesai',
            'diambil_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'pengambilan',
            'action' => 'selesai',
            'catatan' => null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('pengambilan.index')->with('status', 'Barang berhasil diserahkan ke customer.');
    }
}
