<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\PengaturanKeuangan;
use App\Models\SalesOrderPrint;
use App\Services\OrderDocumentService;
use App\Services\OrderPricingService;
use App\Support\ResolvesOrderType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function show(string $type, int $id): View
    {
        $this->authorizeAccess();

        $order = $this->resolveOrder($type, $id);
        $order->load('customer', 'kasir');

        // The "nota pengganti" (replacement invoice) feature only exists for
        // Order Outdoor — Indoor/Artwork models don't define this relation.
        if ($type === 'outdoor') {
            $order->load('replaces');
        }

        $rawItems = match ($type) {
            'indoor' => $order->detailItems(),
            'outdoor' => $order->items()->with('hargaCetak')->get(),
            'artwork' => $order->items,
            default => abort(404),
        };

        $items = $this->pricingService->detailedLineItems($type, $order, $rawItems);
        $printRecord = SalesOrderPrint::query()->where('order_type', $type)->where('order_id', $id)->first();
        $canReprint = $this->canReprint();

        // DP and hutang orders are paid in more than one visit. The nota
        // lists every receipt so the customer can see how the balance was
        // settled, even after the order has flipped to lunas.
        $paymentHistory = OrderPayment::query()->forOrder($type, $id)
            ->whereIn('jenis', ['dp', 'pelunasan_dp', 'pelunasan_hutang'])
            ->orderBy('created_at')->orderBy('id')->get()
            ->groupBy(fn (OrderPayment $payment) => $payment->jenis.'|'.$payment->created_at?->format('Y-m-d H:i'))
            ->map(fn ($rows) => (object) [
                'jenis' => $rows->first()->jenis,
                'label' => match ($rows->first()->jenis) {
                    'dp' => 'DP',
                    default => 'Pelunasan',
                },
                'tanggal' => $rows->first()->created_at,
                'cara_bayar' => $rows->pluck('cara_bayar')->unique()
                    ->map(fn ($cara) => OrderPayment::CARA_BAYAR_LABELS[$cara] ?? $cara)->implode('+'),
                'jumlah' => (float) $rows->sum('jumlah'),
            ])->values();

        return view('invoice.show', [
            'type' => $type,
            'order' => $order,
            'items' => $items,
            'paymentHistory' => $paymentHistory,
            'pengaturan' => PengaturanKeuangan::query()->first(),
            'printRecord' => $printRecord,
            'canPrintSalesOrder' => ! $printRecord || $canReprint,
            'canReprintSalesOrder' => $canReprint,
        ]);
    }

    public function registerPrint(string $type, int $id): JsonResponse
    {
        $this->authorizeAccess();
        $this->resolveOrder($type, $id);

        $result = DB::transaction(function () use ($type, $id): array {
            // Lock the parent order too: a unique child row that does not yet
            // exist cannot itself be locked, so this serializes simultaneous
            // first-print requests from two browser tabs.
            $model = OrderDocumentService::MODELS[$type];
            $model::query()->lockForUpdate()->findOrFail($id);
            $record = SalesOrderPrint::query()
                ->where('order_type', $type)
                ->where('order_id', $id)
                ->lockForUpdate()
                ->first();

            if ($record && ! $this->canReprint()) {
                return ['allowed' => false, 'record' => $record];
            }

            if (! $record) {
                $record = SalesOrderPrint::create([
                    'order_type' => $type,
                    'order_id' => $id,
                    'print_count' => 1,
                    'first_printed_by' => auth()->id(),
                    'first_printed_at' => now(),
                    'last_printed_by' => auth()->id(),
                    'last_printed_at' => now(),
                ]);
            } else {
                $record->update([
                    'print_count' => $record->print_count + 1,
                    'last_printed_by' => auth()->id(),
                    'last_printed_at' => now(),
                ]);
            }

            return ['allowed' => true, 'record' => $record];
        });

        if (! $result['allowed']) {
            return response()->json([
                'message' => 'Sales Order sudah pernah dicetak. Cetak ulang hanya dapat dilakukan oleh Admin Kasir.',
            ], 409);
        }

        return response()->json([
            'message' => $result['record']->print_count > 1 ? 'Cetak ulang diizinkan.' : 'Cetak pertama dicatat.',
            'print_count' => $result['record']->print_count,
        ]);
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->user()->hasPermission('kasir.view')
                || auth()->user()->hasPermission('pengambilan.view')
                || auth()->user()->hasPermission('customer-service.view')
                || auth()->user()->hasPermission('keuangan.view'),
            403
        );
    }

    private function canReprint(): bool
    {
        return auth()->user()->role?->name === 'admin-kasir';
    }
}
