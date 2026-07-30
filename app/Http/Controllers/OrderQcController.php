<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderQcController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'qc')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer')->where('status', 'qc')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $artworkOrders = OrderArtwork::query()->with('customer')->where('status', 'qc')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorOrders->pluck('id'))
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorOrders->pluck('id'));

        return view('order-qc.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'outdoorComments', 'outdoorUnread'));
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        $data = $request->validate([
            'action' => ['required', 'in:selesai,lanjut'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            'status' => 'siap_diambil',
            'qc_by' => auth()->id(),
            'qc_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'qc',
            'action' => $data['action'],
            'catatan' => $data['catatan'] ?? null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-qc.index')->with('status', 'Order siap diambil customer.');
    }
}
