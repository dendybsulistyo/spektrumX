<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Models\PrinterOutdoor;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderBungkusController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        return view('order-bungkus.index', $this->loadData());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'bungkus')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer', 'items')->where('status', 'bungkus')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $artworkOrders = OrderArtwork::query()->with('customer')->where('status', 'bungkus')
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
        $order = $this->resolveOrder($type, $id);

        abort_if($order->status !== 'bungkus', 422, 'Order ini sudah tidak di antrian bungkus.');
        abort_if(OrderReworkRequest::forOrder($type, $id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');

        $data = $request->validate([
            'action' => ['required', 'in:selesai,lanjut'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            'status' => 'siap_diambil',
            'bungkus_by' => auth()->id(),
            'bungkus_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'bungkus',
            'action' => $data['action'],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-bungkus.index')->with('status', 'Order siap diambil customer.');
    }
}
