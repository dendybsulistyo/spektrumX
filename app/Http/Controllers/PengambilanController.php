<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderReworkRequest;
use App\Models\PrinterOutdoor;
use App\Services\DeliveryOrderService;
use App\Services\StageProgressService;
use App\Support\ResolvesOrderDetailType;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('pengambilan.index', $this->loadData($search) + ['search' => $search]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(string $search = ''): array
    {
        $itemsByType = $this->stageProgress->itemsAtStage(self::STAGE, [
            'indoor' => true, 'outdoor' => true,
        ], outdoorWith: ['order.customer', 'order.cancelRequestedBy']);

        $indoorItems = $itemsByType['indoor'] ?? collect();
        $outdoorItems = $itemsByType['outdoor'] ?? collect();

        if ($search !== '') {
            $matches = fn ($items) => Str::contains(
                $items->first()->order->customer?->NmCust.' '.$items->first()->order->NoOrder, $search, ignoreCase: true
            );
            $indoorItems = $indoorItems->filter($matches);
            $outdoorItems = $outdoorItems->filter($matches);
        }

        $outdoorIds = $outdoorItems->keys();

        $outdoorComments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $outdoorIds)
            ->orderBy('created_at')->get()->groupBy('order_id');

        $outdoorUnread = OrderComment::unreadCountsFor('outdoor', $outdoorIds);

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');

        $pendingRework = OrderReworkRequest::pendingMap();
        $canApproveRework = auth()->user()->hasPermission('order-rework.approve');

        $salesTransactions = $this->salesTransactions($search);

        return compact('indoorItems', 'outdoorItems', 'outdoorComments', 'outdoorUnread', 'printerNames', 'pendingRework', 'canApproveRework', 'salesTransactions');
    }

    private function salesTransactions(string $search = '')
    {
        $queries = collect();
        foreach (['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor'] as $type => $orderTable) {
            $payments = DB::table('order_payments')
                ->where('order_type', $type)
                ->select('order_id', DB::raw('SUM(jumlah) as payment_total'))
                ->groupBy('order_id');

            // Lunas is anchored on the indexed document list. Starting from
            // the 150k+ historical order rows made MySQL scan the full order
            // table even though only issued invoices belong in this screen.
            $queries->push(DB::table('order_documents as document')
                ->join($orderTable.' as orders', 'orders.id', '=', 'document.order_id')
                ->leftJoin('customers as customer', 'customer.KdCust', '=', 'orders.KdCust')
                ->leftJoinSub(clone $payments, 'payments', 'payments.order_id', '=', 'orders.id')
                ->where('document.kind', 'inv')->where('document.order_type', $type)->where('document.sequence', 1)
                ->where('orders.status_bayar', 'lunas')->where('orders.status', '!=', 'batal')->whereNull('orders.invoice_voided_at')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('sales_transaction_archives as archive')
                    ->where('archive.order_type', $type)->whereColumn('archive.order_id', 'orders.id'))
                ->selectRaw("orders.id as order_id, document.number as invoice, COALESCE(NULLIF(TRIM(customer.NmCust), ''), orders.KdCust) as customer, orders.TglOrder as sales_order_date, orders.status_bayar, orders.jumlah_piutang, orders.jumlah_dibayar, COALESCE(payments.payment_total, 0) as payment_total, ? as order_type", [$type]));

            // DP/Hutang may not have an invoice yet; they still need to be
            // visible so Pengambilan can require settlement before release.
            $queries->push(DB::table($orderTable.' as orders')
                ->leftJoin('customers as customer', 'customer.KdCust', '=', 'orders.KdCust')
                ->leftJoinSub(clone $payments, 'payments', 'payments.order_id', '=', 'orders.id')
                ->where('orders.status', '!=', 'batal')
                ->whereNull('orders.invoice_voided_at')
                ->whereIn('orders.status_bayar', ['dp', 'hutang'])
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('sales_transaction_archives as archive')
                    ->where('archive.order_type', $type)->whereColumn('archive.order_id', 'orders.id'))
                ->selectRaw("orders.id as order_id, orders.NoOrder as invoice, COALESCE(NULLIF(TRIM(customer.NmCust), ''), orders.KdCust) as customer, orders.TglOrder as sales_order_date, orders.status_bayar, orders.jumlah_piutang, orders.jumlah_dibayar, COALESCE(payments.payment_total, 0) as payment_total, ? as order_type", [$type]));
        }

        $query = $queries->shift();
        foreach ($queries as $other) {
            $query->unionAll($other);
        }

        $paginator = DB::query()->fromSub($query, 'sales_transactions')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('customer', 'like', '%'.$search.'%')
                ->orWhere('invoice', 'like', '%'.$search.'%')))
            ->orderByRaw('LOWER(customer)')
            ->orderBy('sales_order_date')
            ->orderBy('invoice')
            ->paginate(50, ['*'], 'transaksi_page')
            ->withQueryString();

        // Do not present a stale jumlah_piutang for DP orders. It is a
        // denormalized snapshot and historical/edited orders can differ
        // from the current final total, which previously produced an
        // unbalanced journal at settlement time.
        $rows = $paginator->getCollection();
        $orders = [
            'indoor' => OrderIndoor::query()
                ->whereIn('id', $rows->where('order_type', 'indoor')->pluck('order_id'))
                ->get()->keyBy('id'),
            'outdoor' => OrderOutdoor::query()
                ->whereIn('id', $rows->where('order_type', 'outdoor')->pluck('order_id'))
                ->get()->keyBy('id'),
        ];

        $paginator->setCollection($rows->map(function ($transaction) use ($orders) {
            if ($transaction->status_bayar !== 'dp') {
                return $transaction;
            }

            $order = $orders[$transaction->order_type]->get($transaction->order_id);
            if ($order) {
                $totalFinal = Rupiah::bulatkan(
                    $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total
                );
                $transaction->jumlah_piutang = max($totalFinal - (float) $order->jumlah_dibayar, 0);
            }

            return $transaction;
        }));

        return $paginator;
    }

    public function archiveTransaction(string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['indoor', 'outdoor'], true), 404);
        $model = $type === 'indoor' ? OrderIndoor::class : OrderOutdoor::class;

        $error = DB::transaction(function () use ($model, $type, $id): ?string {
            $order = $model::query()->lockForUpdate()->findOrFail($id);
            if ($order->status_bayar !== 'lunas' || (float) $order->jumlah_piutang > 0) {
                return 'Transaksi belum lunas dan belum dapat disimpan.';
            }

            $received = (float) OrderPayment::query()->forOrder($type, $id)->sum('jumlah');
            $expected = (float) $order->jumlah_dibayar;
            if ($expected <= 0 || $received + 0.01 < $expected) {
                return 'Penerimaan pembayaran belum tercatat lengkap di Keuangan. Hubungi Kasir sebelum menyimpan transaksi.';
            }

            DB::table('sales_transaction_archives')->updateOrInsert(
                ['order_type' => $type, 'order_id' => $id],
                ['archived_by' => auth()->id(), 'archived_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );

            return null;
        });

        if ($error) {
            return redirect()->route('pengambilan.index', ['tab' => 'transaksi'])->with('error', $error);
        }

        return redirect()->route('pengambilan.index', ['tab' => 'transaksi'])
            ->with('status', 'Transaksi lunas sudah tersimpan dan dihilangkan dari daftar.');
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
