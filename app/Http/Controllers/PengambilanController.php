<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
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

        $artworkOrders = OrderArtwork::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        return view('pengambilan.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders'));
    }

    public function serahkan(string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        if ($order->status_bayar === 'dp' && (float) $order->jumlah_piutang > 0) {
            return back()->with('error', 'Order ini masih ada sisa DP Rp '.number_format($order->jumlah_piutang, 0, ',', '.').' yang belum dilunasi. Lunasi dulu lewat halaman Bayar.');
        }

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
