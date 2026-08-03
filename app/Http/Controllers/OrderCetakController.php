<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Models\PrinterOutdoor;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderCetakController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        return view('order-cetak.index', $this->loadData());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
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

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        return compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'outdoorComments', 'outdoorUnread', 'printerNames', 'pendingRework', 'canApproveRework');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_if($type === 'outdoor', 422, 'Order outdoor diselesaikan lewat progress per item, bukan Update Status.');

        $order = $this->resolveOrder($type, $id);

        abort_if($order->status !== 'cetak', 422, 'Order ini sudah tidak di antrian cetak.');
        abort_if(OrderReworkRequest::forOrder($type, $id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');

        $data = $request->validate([
            'action' => ['required', 'in:selesai,lanjut'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            'status' => 'finishing',
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

        return redirect()->route('order-cetak.index', ['tab' => $type])->with('status', 'Order dipindahkan ke antrian Finishing.');
    }

    /**
     * Petugas cetak mengerjakan item outdoor secara bertahap — tiap submit
     * menambah qty_diproses (dibatasi maksimal sisa Qty pesanan). Begitu
     * semua item dalam order sudah tuntas, order otomatis pindah ke
     * Finishing, tanpa perlu tombol "Update Status" manual seperti
     * Indoor/Artwork.
     */
    public function updateProgress(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        $order = $item->order;

        abort_if($order->cancel_requested_at, 422, 'Order ini sedang menunggu persetujuan pembatalan.');
        abort_if($order->status !== 'cetak', 422, 'Order ini sudah tidak di antrian cetak.');
        abort_if(OrderReworkRequest::forOrder('outdoor', $order->id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.$item->sisaQty()],
        ]);

        $item->increment('qty_diproses', $data['qty']);

        if ($order->items()->get()->every(fn (OrderOutdoorDetail $i) => $i->isSelesai())) {
            $this->finishOutdoorOrder($order);

            return redirect()->route('order-cetak.index', ['tab' => 'outdoor'])->with('status', "Order {$order->NoOrder} tuntas, dipindahkan ke antrian Finishing.");
        }

        return redirect()->route('order-cetak.index', ['tab' => 'outdoor'])->with('status', 'Progress tersimpan.');
    }

    /**
     * Fallback for an outdoor order whose items are already fully
     * progressed but never got the automatic push into Finishing — e.g. an
     * order that was sent back to Layout and re-entered Cetak with its
     * qty_diproses already maxed out, so there was no "last unit" click left
     * to trigger updateProgress()'s transition. Since there's nothing left
     * to click in that state, this gives the operator an explicit way to
     * unstick it.
     */
    public function finishOutdoor(OrderOutdoor $order): RedirectResponse
    {
        abort_if($order->status !== 'cetak', 422, 'Order ini sudah tidak di antrian cetak.');
        abort_if(OrderReworkRequest::forOrder('outdoor', $order->id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');
        abort_unless($order->items()->get()->every(fn (OrderOutdoorDetail $i) => $i->isSelesai()), 422, 'Masih ada item yang belum tuntas.');

        $this->finishOutdoorOrder($order);

        return redirect()->route('order-cetak.index', ['tab' => 'outdoor'])->with('status', "Order {$order->NoOrder} dipindahkan ke antrian Finishing.");
    }

    private function finishOutdoorOrder(OrderOutdoor $order): void
    {
        $order->update([
            'status' => 'finishing',
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
    }
}
