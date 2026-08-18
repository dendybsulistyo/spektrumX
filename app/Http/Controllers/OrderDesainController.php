<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\OrderComment;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderReworkRequest;
use App\Models\PrinterOutdoor;
use App\Services\StageProgressService;
use App\Support\PageVersion;
use App\Support\ResolvesOrderDetailType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderDesainController extends Controller
{
    use ResolvesOrderDetailType;

    private const STAGE = 'desain';

    private const PAGE_VERSION_KEY = 'order-desain';

    public function __construct(private StageProgressService $stageProgress) {}

    public function index(): View
    {
        return view('order-desain.index', $this->loadData() + [
            'pageVersion' => PageVersion::get(self::PAGE_VERSION_KEY),
        ]);
    }

    public function version(): JsonResponse
    {
        return response()->json(['version' => PageVersion::get(self::PAGE_VERSION_KEY)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        $user = auth()->user();
        $showIndoor = $user->hasPermission('order-indoor.view');
        $showOutdoor = $user->hasPermission('order-outdoor.view');

        $itemsByType = $this->stageProgress->itemsAtStage(self::STAGE, [
            'indoor' => $showIndoor,
            'outdoor' => $showOutdoor,
            'artwork' => false,
        ], outdoorWith: ['order.customer', 'order.cancelRequestedBy', 'order.createdBy']);

        $indoorItems = $itemsByType['indoor'] ?? collect();

        $outdoorItems = collect();
        $outdoorNeedsReply = collect();
        $outdoorComments = collect();
        $outdoorUnread = collect();
        $printerNames = collect();
        $bahanNames = collect();

        if ($showOutdoor) {
            $outdoorItems = $itemsByType['outdoor'] ?? collect();

            // An order that already left the desain stage can still resurface
            // here if someone (typically the Status Cetak operator) posts a new
            // discussion reply on it — the desain operator needs to see and
            // answer it even though "Update Status" no longer applies.
            $needsReplyIds = OrderComment::orderIdsWithUnreadFor('outdoor')->diff($outdoorItems->keys());

            $outdoorNeedsReply = OrderOutdoor::query()->with('customer', 'items', 'createdBy')
                ->whereIn('id', $needsReplyIds)
                ->orderByDesc('TglOrder')->orderByDesc('NoOrder')->get();

            $allOutdoorIds = $outdoorItems->keys()->merge($outdoorNeedsReply->pluck('id'));

            $outdoorComments = OrderComment::with('user')
                ->where('order_type', 'outdoor')->whereIn('order_id', $allOutdoorIds)
                ->orderBy('created_at')->get()->groupBy('order_id');

            $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $allOutdoorIds);

            $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');
            $bahanNames = BahanCetakOutdoor::pluck('NmBhn', 'NoCetak');
        }

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = $user->hasPermission('order-rework.approve');

        return compact('indoorItems', 'outdoorItems', 'outdoorNeedsReply', 'outdoorComments', 'outdoorUnread', 'printerNames', 'bahanNames', 'pendingRework', 'canApproveRework');
    }

    /**
     * Moves N qty of one line item from Desain to Cetak. Whatever qty isn't
     * submitted stays behind at Desain — it doesn't wait for the rest of
     * the line to catch up. See StageProgressService::advance().
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

        PageVersion::touch(self::PAGE_VERSION_KEY);

        $message = "{$result['moved']} unit dipindahkan ke antrian Cetak.".($result['stageCleared'] ? ' Baris item ini tuntas di Desain.' : '');

        return redirect()->route('order-desain.index', ['tab' => $type])->with('status', $message);
    }

    /**
     * Free-text "Gabungan" note per outdoor item — catatan manual operator
     * desain, tidak terikat validasi/format tertentu.
     *
     * Untuk order 1 pcs, field ini terkunci begitu sudah terisi supaya
     * tidak tertimpa operator lain — lihat updateNmFile().
     */
    public function updateGabungan(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        if ((int) $item->Qty === 1 && filled($item->gabungan)) {
            return redirect()->route('order-desain.index', ['tab' => 'outdoor'])
                ->with('error', 'Gabungan sudah terisi dan tidak bisa diubah untuk order 1 pcs.');
        }

        $data = $request->validate([
            'gabungan' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update(['gabungan' => $data['gabungan'] ?? null]);

        PageVersion::touch(self::PAGE_VERSION_KEY);

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', 'Gabungan disimpan.');
    }

    /**
     * Nama file desain per outdoor item — sama pola simpan/submit dengan
     * updateGabungan() (auto-submit onchange di view).
     *
     * Untuk order 1 pcs, field ini terkunci begitu sudah terisi supaya
     * tidak tertimpa operator lain.
     */
    public function updateNmFile(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        if ((int) $item->Qty === 1 && filled($item->NmFile)) {
            return redirect()->route('order-desain.index', ['tab' => 'outdoor'])
                ->with('error', 'Nama file sudah terisi dan tidak bisa diubah untuk order 1 pcs.');
        }

        $data = $request->validate([
            'NmFile' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update(['NmFile' => $data['NmFile'] ?? '']);

        PageVersion::touch(self::PAGE_VERSION_KEY);

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', 'Nama file disimpan.');
    }
}
