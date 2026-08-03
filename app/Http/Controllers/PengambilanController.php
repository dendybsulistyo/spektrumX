<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengambilanController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        return view('pengambilan.index', $this->loadData());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $artworkOrders = OrderArtwork::query()->with('customer')->where('status', 'siap_diambil')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorOrders->pluck('id'))
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorOrders->pluck('id'));

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        return compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'outdoorComments', 'outdoorUnread', 'pendingRework', 'canApproveRework');
    }

    public function serahkan(string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        abort_if($order->status !== 'siap_diambil', 422, 'Order ini sudah tidak di antrian pengambilan.');
        abort_if(OrderReworkRequest::forOrder($type, $id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');

        if ($order->status_bayar === 'dp' && (float) $order->jumlah_piutang > 0) {
            return back()->with('error', 'Order ini masih ada sisa DP Rp '.number_format($order->jumlah_piutang, 0, ',', '.').' yang belum dilunasi. Lunasi dulu lewat halaman Bayar.');
        }

        $order->update([
            'status' => 'selesai',
            'diambil_at' => now(),
            'pengambilan_by' => auth()->id(),
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
