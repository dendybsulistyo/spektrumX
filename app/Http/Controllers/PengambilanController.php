<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\OrderReworkRequest;
use App\Models\PrinterOutdoor;
use App\Services\StageProgressService;
use App\Support\ResolvesOrderDetailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengambilanController extends Controller
{
    use ResolvesOrderDetailType;

    private const STAGE = 'siap_diambil';

    public function __construct(private StageProgressService $stageProgress) {}

    public function index(): View
    {
        return view('pengambilan.index', $this->loadData());
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        $itemsByType = $this->stageProgress->itemsAtStage(self::STAGE, [
            'indoor' => true, 'outdoor' => true, 'artwork' => true,
        ], outdoorWith: ['order.customer', 'order.cancelRequestedBy']);

        $indoorItems = $itemsByType['indoor'] ?? collect();
        $outdoorItems = $itemsByType['outdoor'] ?? collect();
        $artworkItems = $itemsByType['artwork'] ?? collect();

        $outdoorIds = $outdoorItems->keys();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorIds)
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorIds);

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        return compact('indoorItems', 'outdoorItems', 'artworkItems', 'outdoorComments', 'outdoorUnread', 'printerNames', 'pendingRework', 'canApproveRework');
    }

    /**
     * Moves N qty of one line item from Siap Diambil to Selesai — i.e. the
     * customer physically took N units. Partial pickups are logged
     * (OrderStatusNote.qty) same as every other stage, so a customer who
     * picks up their order in two trips has both trips on record. Once
     * every item's qty on the order has fully moved to Selesai, the
     * order's header status flips to 'selesai' by itself (via
     * recalculateStatus()) — same trigger Kasir/reporting already expect,
     * just now reached bottom-up from items instead of set directly here.
     */
    public function updateItem(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->resolveDetailItem($type, $id);
        $order = $item->order;

        if ($order->status_bayar === 'dp' && (float) $order->jumlah_piutang > 0) {
            return back()->with('error', 'Order ini masih ada sisa DP Rp '.number_format($order->jumlah_piutang, 0, ',', '.').' yang belum dilunasi. Lunasi dulu lewat halaman Bayar.');
        }

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'kontak_penerima' => ['required', 'string', 'max:50'],
        ]);

        $qty = $data['qty'] ?? $item->qtyAt(self::STAGE);
        $catatan = "Diambil oleh: {$data['nama_penerima']} (Kontak: {$data['kontak_penerima']})";

        $result = $this->stageProgress->advance($item, self::STAGE, $qty, $catatan, auth()->id());

        if ($result['order']->status === 'selesai' && ! $result['order']->diambil_at) {
            $result['order']->update(['diambil_at' => now(), 'pengambilan_by' => auth()->id()]);
        }

        $message = "{$result['moved']} unit diserahkan ke customer.".($result['stageCleared'] ? ' Baris item ini tuntas diambil.' : '');

        return redirect()->route('pengambilan.index')->with('status', $message);
    }
}
