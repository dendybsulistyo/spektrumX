<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
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
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OrderDesainController extends Controller
{
    use ResolvesOrderType;

    public function index(): View
    {
        return view('order-desain.index', $this->loadData());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        $indoorOrders = OrderIndoor::query()->with('customer')->where('status', 'desain')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $outdoorOrders = OrderOutdoor::query()->with('customer', 'cancelRequestedBy', 'items', 'createdBy')->where('status', 'desain')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $artworkOrders = OrderArtwork::query()->with('customer', 'items')->where('status', 'desain')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        // Indoor and Artwork list one row per physical unit to design (qty
        // per item) — a Qty 50 item shows as 50 rows so desain staff can
        // tick off pieces one by one. For Indoor specifically, only items
        // with Panjang/Lebar entered get expanded this way — Qty "count"
        // products (no dimension input) stay as a single row. Outdoor
        // displays one row per order as usual (it previously had its own
        // per-unit tracking here, but that turned out not to be wanted).
        $indoorRows = $this->expandRows(
            $indoorOrders,
            fn ($o) => $o->detailItems(),
            'Judul',
            fn ($item) => (float) $item->Panjang > 0 && (float) $item->Lebar > 0,
        );
        $artworkRows = $this->expandRows($artworkOrders, fn ($o) => $o->items, 'Judul');

        // An order that already left the desain stage can still resurface
        // here if someone (typically the Status Cetak operator) posts a new
        // discussion reply on it — the desain operator needs to see and
        // answer it even though "Update Status" no longer applies.
        $needsReplyIds = OrderComment::orderIdsWithUnreadFor('outdoor')->diff($outdoorOrders->pluck('id'));

        $outdoorNeedsReply = OrderOutdoor::query()->with('customer', 'items', 'createdBy')
            ->whereIn('id', $needsReplyIds)
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

        $allOutdoorIds = $outdoorOrders->pluck('id')->merge($outdoorNeedsReply->pluck('id'));

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $allOutdoorIds)
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $allOutdoorIds);

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');
        $bahanNames = BahanCetakOutdoor::pluck('NmBhn', 'NoCetak');

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        return compact('indoorOrders', 'outdoorOrders', 'outdoorNeedsReply', 'artworkOrders', 'indoorRows', 'artworkRows', 'outdoorComments', 'outdoorUnread', 'printerNames', 'bahanNames', 'pendingRework', 'canApproveRework');
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
                $size = $this->formatSize($item);

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

    private function formatSize(object $item): ?string
    {
        if ((float) $item->Panjang <= 0 || (float) $item->Lebar <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.')
            .' x '
            .rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.')
            .' cm';
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        // Guards against a stale/replayed request (old browser tab, double
        // submit) silently sending an order that already moved past this
        // stage back into it — transitions only ever go forward.
        abort_if($order->status !== 'desain', 422, 'Order ini sudah tidak di antrian desain.');
        abort_if(OrderReworkRequest::forOrder($type, $id)->pending()->exists(), 422, 'Order ini sedang menunggu persetujuan pembatalan/ulang proses.');

        $data = $request->validate([
            'action' => ['required', 'in:selesai,lanjut'],
        ]);

        $order->update([
            'status' => 'cetak',
            'desain_by' => auth()->id(),
            'desain_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'desain',
            'action' => $data['action'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-desain.index', ['tab' => $type])->with('status', 'Order dipindahkan ke antrian cetak.');
    }

    /**
     * Free-text "Gabungan" note per outdoor item — catatan manual operator
     * desain, tidak terikat validasi/format tertentu.
     */
    public function updateGabungan(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        $data = $request->validate([
            'gabungan' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update(['gabungan' => $data['gabungan'] ?? null]);

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', 'Gabungan disimpan.');
    }
}
