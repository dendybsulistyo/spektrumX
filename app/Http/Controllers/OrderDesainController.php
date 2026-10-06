<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\OrderComment;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Models\PrinterOutdoor;
use App\Services\StageProgressService;
use App\Support\PageVersion;
use App\Support\ResolvesOrderDetailType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OrderDesainController extends Controller
{
    use ResolvesOrderDetailType;

    private const STAGE = 'desain';

    private const PAGE_VERSION_KEY = 'order-desain';

    public function __construct(private StageProgressService $stageProgress) {}

    public function index(Request $request): View
    {
        $groupBy = $request->string('group_by')->toString();
        if (in_array($groupBy, ['order', 'division', 'product'], true)) {
            $request->session()->put('order-desain.group-by', $groupBy);
        } else {
            $groupBy = $request->session()->get('order-desain.group-by', 'order');
        }

        return view('order-desain.index', $this->loadData() + [
            'pageVersion' => PageVersion::get(self::PAGE_VERSION_KEY),
            'groupBy' => $groupBy,
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
        ], indoorWith: ['order.customer', 'produk.kategori', 'produkArtwork.kategori'], outdoorWith: ['order.customer', 'order.cancelRequestedBy', 'order.createdBy']);

        $indoorItems = $itemsByType['indoor'] ?? collect();

        $outdoorItems = collect();
        $outdoorNeedsReply = collect();
        $outdoorComments = collect();
        $outdoorUnread = collect();
        $printerNames = collect();
        $bahanNames = collect();
        $layoutRevisionItems = collect();

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
            $layoutRevisionItems = $this->activeLayoutRevisionsFor($outdoorItems->flatten(1));
        }

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = $user->hasPermission('order-rework.approve');

        return compact('indoorItems', 'outdoorItems', 'outdoorNeedsReply', 'outdoorComments', 'outdoorUnread', 'printerNames', 'bahanNames', 'layoutRevisionItems', 'pendingRework', 'canApproveRework');
    }

    /**
     * Moves N qty of one line item from Desain to Cetak. Whatever qty isn't
     * submitted stays behind at Desain — it doesn't wait for the rest of
     * the line to catch up. See StageProgressService::advance().
     */
    public function updateItem(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->resolveDetailItem($type, $id);
        $layoutRevision = $item instanceof OrderOutdoorDetail
            ? $this->activeLayoutRevisionFor($item)
            : null;

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $qty = $data['qty'] ?? $item->qtyAt(self::STAGE);

        $result = $this->stageProgress->advance($item, self::STAGE, $qty, $data['catatan'] ?? null, auth()->id());

        if ($layoutRevision && $result['stageCleared']) {
            $sourceStage = OrderReworkRequest::STAGE_LABELS[$layoutRevision->current_stage] ?? $layoutRevision->current_stage;
            $fileName = $result['item']->NmFile ?: 'Tanpa nama file';

            OrderStatusNote::create([
                'order_type' => 'outdoor',
                'order_id' => $result['order']->id,
                'order_detail_id' => $result['item']->id,
                'qty' => $result['moved'],
                'stage' => self::STAGE,
                'action' => 'revisi_selesai',
                'catatan' => "Materi diperbaiki Operator Layout. Nama file: {$fileName}. Dikembalikan dari {$sourceStage} (alasan: {$layoutRevision->reason}).",
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        }

        PageVersion::touch(self::PAGE_VERSION_KEY);

        $message = "{$result['moved']} unit dipindahkan ke antrian Cetak.".($result['stageCleared'] ? ' Baris item ini tuntas di Desain.' : '');
        if ($layoutRevision && $result['stageCleared']) {
            $message .= ' Revisi Layout ditandai selesai.';
        }

        return redirect()->route('order-desain.index', ['tab' => $type])->with('status', $message);
    }

    /**
     * Free-text "Gabungan" note per outdoor item — catatan manual operator
     * desain, tidak terikat validasi/format tertentu.
     *
     * Nilainya tetap dapat diperbarui oleh Operator Layout selama item
     * dikerjakan di halaman Layout.
     */
    public function updateGabungan(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
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
     * Nama File dikelola oleh Operator File. Di halaman Operator Layout nilai
     * ini hanya ditampilkan sebagai referensi dan tidak dapat diperbarui.
     */
    public function updateNmFile(Request $request, OrderOutdoorDetail $item): RedirectResponse
    {
        $layoutRevision = $this->activeLayoutRevisionFor($item);
        $user = auth()->user();
        $canEditAsFileOperator = $user->hasPermission('order-desain.nmfile-manage');

        abort_unless($canEditAsFileOperator, 403);

        $data = $request->validate([
            'NmFile' => [$layoutRevision ? 'required' : 'nullable', 'string', 'max:255'],
        ]);

        $item->update(['NmFile' => $data['NmFile'] ?? '']);

        PageVersion::touch(self::PAGE_VERSION_KEY);

        $message = $layoutRevision
            ? 'Nama file revisi disimpan. Kirim ke Cetak setelah materi selesai diperbaiki.'
            : 'Nama file disimpan.';

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', $message);
    }

    /**
     * @return Collection<int, OrderReworkRequest>
     */
    private function activeLayoutRevisionsFor(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $requestsByOrder = OrderReworkRequest::query()
            ->where('order_type', 'outdoor')
            ->where('action', 'ulang')
            ->where('target_stage', self::STAGE)
            ->where('status', 'approved')
            ->whereIn('order_id', $items->pluck('order_outdoor_id')->unique())
            ->with('requestedBy')
            ->orderByDesc('resolved_at')
            ->get()
            ->groupBy('order_id');

        return $items->mapWithKeys(function (OrderOutdoorDetail $item) use ($requestsByOrder) {
            $request = $requestsByOrder->get($item->order_outdoor_id, collect())
                ->first(fn (OrderReworkRequest $request) => $this->layoutRevisionApplies($request, $item)
                    && ! $this->layoutRevisionCompleted($request, $item));

            return $request ? [$item->id => $request] : [];
        });
    }

    private function activeLayoutRevisionFor(OrderOutdoorDetail $item): ?OrderReworkRequest
    {
        $item->loadMissing('layoutRevisionCompletions');

        return OrderReworkRequest::query()
            ->where('order_type', 'outdoor')
            ->where('order_id', $item->order_outdoor_id)
            ->where('action', 'ulang')
            ->where('target_stage', self::STAGE)
            ->where('status', 'approved')
            ->with('requestedBy')
            ->orderByDesc('resolved_at')
            ->get()
            ->first(fn (OrderReworkRequest $request) => $this->layoutRevisionApplies($request, $item)
                && ! $this->layoutRevisionCompleted($request, $item));
    }

    private function layoutRevisionApplies(OrderReworkRequest $request, OrderOutdoorDetail $item): bool
    {
        if ($item->qtyAt(self::STAGE) < 1) {
            return false;
        }

        $detailIds = collect($request->order_detail_ids)->map(fn ($id) => (int) $id);

        return $detailIds->isEmpty() || $detailIds->contains($item->id);
    }

    private function layoutRevisionCompleted(OrderReworkRequest $request, OrderOutdoorDetail $item): bool
    {
        if (! $request->resolved_at) {
            return false;
        }

        return $item->layoutRevisionCompletions
            ->contains(fn (OrderStatusNote $note) => $note->created_at->gte($request->resolved_at));
    }
}
