<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $artworkOrders = OrderArtwork::query()->with('customer', 'items')->where('status', 'cetak')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        // Antrian Cetak lists one row per physical unit to print (qty per
        // item), not one row per order — a Qty 50 item shows as 50 rows so
        // print staff can tick off pieces one by one. For Indoor specifically,
        // only items with Panjang/Lebar entered get expanded this way — Qty
        // "count" products (no dimension input) stay as a single row.
        $indoorRows = $this->expandRows(
            $indoorOrders,
            fn ($o) => $o->detailItems(),
            'Judul',
            fn ($item) => (float) $item->Panjang > 0 && (float) $item->Lebar > 0,
        );
        $outdoorRows = $this->expandRows($outdoorOrders, fn ($o) => $o->items, 'NmFile');
        $artworkRows = $this->expandRows($artworkOrders, fn ($o) => $o->items, 'Judul');

        return view('order-cetak.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'indoorRows', 'outdoorRows', 'artworkRows'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $orders
     * @param  (callable(mixed): bool)|null  $shouldExpand  Whether an item gets split into one row per qty unit; items that don't qualify stay a single row. Defaults to always expanding.
     * @return Collection<int, object>
     */
    private function expandRows(Collection $orders, callable $itemsResolver, string $nameField, ?callable $shouldExpand = null): Collection
    {
        $rows = collect();

        foreach ($orders as $order) {
            foreach ($itemsResolver($order) as $item) {
                $qty = max(1, (int) $item->Qty);
                $size = ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                    ? rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.').' x '.rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.').' cm'
                    : null;

                if ($shouldExpand && ! $shouldExpand($item)) {
                    $rows->push((object) [
                        'order' => $order,
                        'itemName' => $item->{$nameField},
                        'size' => $size,
                        'unitIndex' => null,
                        'unitTotal' => $qty,
                    ]);

                    continue;
                }

                for ($unit = 1; $unit <= $qty; $unit++) {
                    $rows->push((object) [
                        'order' => $order,
                        'itemName' => $item->{$nameField},
                        'size' => $size,
                        'unitIndex' => $unit,
                        'unitTotal' => $qty,
                    ]);
                }
            }
        }

        return $rows;
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        if ($type === 'outdoor' && $order->cancel_requested_at) {
            return back()->with('error', 'Order ini sedang menunggu persetujuan pembatalan, tidak bisa diproses lanjut dulu.');
        }

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
}
