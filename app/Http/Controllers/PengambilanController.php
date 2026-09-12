<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\OrderReworkRequest;
use App\Models\PrinterOutdoor;
use App\Services\DeliveryOrderService;
use App\Services\StageProgressService;
use App\Support\ResolvesOrderDetailType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'indoor' => true, 'outdoor' => true,
        ], outdoorWith: ['order.customer', 'order.cancelRequestedBy']);

        $indoorItems = $itemsByType['indoor'] ?? collect();
        $outdoorItems = $itemsByType['outdoor'] ?? collect();

        $outdoorIds = $outdoorItems->keys();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorIds)
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorIds);

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        return compact('indoorItems', 'outdoorItems', 'outdoorComments', 'outdoorUnread', 'printerNames', 'pendingRework', 'canApproveRework');
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

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'request_key' => ['required', 'uuid'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'kontak_penerima' => ['required', 'string', 'max:50'],
            'signature_strokes' => ['required', 'string', 'max:20000'],
        ]);

        $svg = $this->signatureSvg($data['signature_strokes']);
        $path = 'pickup-signatures/'.now()->format('Y/m').'/'.$type.'-'.$order->id.'-'.$item->id.'-'.Str::uuid().'.svg';

        abort_unless(Storage::disk('local')->put($path, $svg), 500, 'Tanda tangan gagal disimpan.');

        try {
            $document = app(DeliveryOrderService::class)->receive(
                $item, $type, $data, $path, hash('sha256', $svg), auth()->id()
            );
            if ($document->snapshot['signature_path'] !== $path) {
                Storage::disk('local')->delete($path);
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 422) {
                return redirect()->route('pengambilan.index')
                    ->with('error', $exception->getMessage());
            }
            throw $exception;
        }

        return redirect()->route('pengambilan.index')->with('status', "Barang diserahkan. DO {$document->number} tersedia di Dokumen SO / DO / Invoice.");
    }

    private function signatureSvg(string $payload): string
    {
        $strokes = json_decode($payload, true);
        if (! is_array($strokes)) {
            throw ValidationException::withMessages(['signature_strokes' => 'Tanda tangan tidak valid. Silakan buat ulang.']);
        }

        $paths = [];
        foreach ($strokes as $stroke) {
            if (! is_array($stroke) || count($stroke) < 2) {
                continue;
            }
            $points = [];
            foreach ($stroke as $point) {
                if (! is_array($point) || count($point) !== 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                    throw ValidationException::withMessages(['signature_strokes' => 'Data tanda tangan tidak valid.']);
                }
                $x = (float) $point[0];
                $y = (float) $point[1];
                if ($x < 0 || $x > 600 || $y < 0 || $y > 220) {
                    throw ValidationException::withMessages(['signature_strokes' => 'Ukuran tanda tangan tidak valid.']);
                }
                $points[] = round($x, 1).' '.round($y, 1);
            }
            $paths[] = '<path d="M '.implode(' L ', $points).'"/>';
        }

        if ($paths === []) {
            throw ValidationException::withMessages(['signature_strokes' => 'Tanda tangan wajib diisi sebelum barang diserahkan.']);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="220" viewBox="0 0 600 220"><g fill="none" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">'.implode('', $paths).'</g></svg>';
    }
}
