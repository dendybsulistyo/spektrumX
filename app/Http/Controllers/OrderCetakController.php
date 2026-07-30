<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderCetakController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'cetak')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer', 'cancelRequestedBy', 'items')->where('status', 'cetak')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $artworkOrders = OrderArtwork::query()->with('customer')->where('status', 'cetak')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorOrders->pluck('id'))
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorOrders->pluck('id'));

        return view('order-cetak.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'outdoorComments', 'outdoorUnread'));
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_if($type === 'outdoor', 422, 'Order outdoor diselesaikan lewat progress per item, bukan Update Status.');

        $order = $this->resolveOrder($type, $id);

        $data = $request->validate([
            'action' => ['required', 'in:selesai,lanjut'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            'status' => 'qc',
            'cetak_by' => auth()->id(),
            'cetak_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'cetak',
            'action' => $data['action'],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-cetak.index')->with('status', 'Order dipindahkan ke antrian QC.');
    }

    /**
     * Petugas cetak mengerjakan item outdoor secara bertahap — tiap submit
     * menambah qty_diproses (dibatasi maksimal sisa Qty pesanan). Begitu
     * semua item dalam order sudah tuntas, order otomatis pindah ke QC,
     * tanpa perlu tombol "Update Status" manual seperti Indoor/Artwork.
     */
    public function updateProgress(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        $order = $item->order;

        abort_if($order->cancel_requested_at, 422, 'Order ini sedang menunggu persetujuan pembatalan.');
        abort_if($order->status !== 'cetak', 422, 'Order ini sudah tidak di antrian cetak.');

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.$item->sisaQty()],
        ]);

        $item->increment('qty_diproses', $data['qty']);

        if ($order->items()->get()->every(fn (OrderOutdoorDetail $i) => $i->isSelesai())) {
            $order->update([
                'status' => 'qc',
                'cetak_by' => auth()->id(),
                'cetak_at' => now(),
            ]);

            OrderStatusNote::create([
                'order_type' => 'outdoor',
                'order_id' => $order->id,
                'stage' => 'cetak',
                'action' => 'selesai',
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            return redirect()->route('order-cetak.index')->with('status', "Order {$order->NoOrder} tuntas, dipindahkan ke antrian QC.");
        }

        return redirect()->route('order-cetak.index')->with('status', 'Progress tersimpan.');
    }
}
