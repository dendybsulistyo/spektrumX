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

class OrderQcController extends Controller
{
    use ResolvesOrderDetailType;

    private const STAGE = 'qc';

    public function __construct(private StageProgressService $stageProgress) {}

    public function index(): View
    {
        return view('order-qc.index', $this->loadData());
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
     * Moves N qty of one line item from QC (Back Office) to Bungkus. See
     * StageProgressService::advance().
     */
    public function updateItem(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->resolveDetailItem($type, $id);

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $qty = $data['qty'] ?? $item->qtyAt(self::STAGE);

        $result = $this->stageProgress->advance($item, self::STAGE, $qty, $data['catatan'] ?? null, auth()->id());

        $message = "{$result['moved']} unit dipindahkan ke antrian Bungkus.".($result['stageCleared'] ? ' Baris item ini tuntas di Back Office.' : '');

        return redirect()->route('order-qc.index', ['tab' => $type])->with('status', $message);
    }
}
