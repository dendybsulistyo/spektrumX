<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\CustomerServiceJobSheet;
use App\Models\HargaCetakOutdoor;
use App\Models\OrderComment;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderStatusNote;
use App\Models\PrinterOutdoor;
use App\Services\OrderCancellationRefund;
use App\Services\ApproverNotificationService;
use App\Services\OrderPricingService;
use App\Services\OrderNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderOutdoorController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
        private readonly ApproverNotificationService $notifier,
        private readonly OrderNumberService $orderNumbers,
    ) {}

    public function index(Request $request): View
    {
        $orders = OrderOutdoor::query()
            ->with('customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NoOrder', 'like', "%{$search}%")
                    ->orWhere('KdCust', 'like', "%{$search}%");
            })
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(15)
            ->withQueryString();

        $comments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $orders->pluck('id'))
            ->orderBy('created_at')->get()->groupBy('order_id');

        $unread = OrderComment::unreadCountsFor('outdoor', $orders->pluck('id'));

        return view('order-outdoor.index', compact('orders', 'comments', 'unread'));
    }

    public function create(Request $request): View
    {
        $jobSheet = $request->integer('job_sheet')
            ? CustomerServiceJobSheet::with('customer')->findOrFail($request->integer('job_sheet'))
            : null;
        $items = $jobSheet
            ? collect($jobSheet->items)->map(fn (array $item) => (object) [
                'NmFile' => mb_substr($jobSheet->folder_file ?: $item['material'], 0, 50),
                'Panjang' => $item['width'],
                'Lebar' => $item['height'],
                'Qty' => $item['quantity'],
                'KdCtk' => '',
                'ada_finishing' => filled($item['finishing'] ?? null),
                'jenis_finishing' => $item['finishing'] ?? null,
            ])
            : null;

        return view('order-outdoor.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : $jobSheet?->customer,
            'items' => $items,
            'sourceJobSheet' => $jobSheet,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    /** Create a new invoice from an approved cancellation without deleting its history. */
    public function createReplacement(OrderOutdoor $orderOutdoor): View
    {
        abort_unless(
            $orderOutdoor->status === 'batal' && $orderOutdoor->invoice_voided_at && ! $orderOutdoor->replacement()->exists(),
            404
        );

        return view('order-outdoor.create', [
            'replacementOrder' => $orderOutdoor,
            'selectedCustomer' => $orderOutdoor->customer,
            'items' => $orderOutdoor->items,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function store(StoreOrderOutdoorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $replacement = null;

        DB::transaction(function () use ($data, &$replacement) {
            if (! empty($data['replacement_order_id'])) {
                $replacement = OrderOutdoor::lockForUpdate()->findOrFail($data['replacement_order_id']);
                abort_unless(
                    $replacement->status === 'batal' && $replacement->invoice_voided_at && ! $replacement->replacement()->exists(),
                    422,
                    'Nota asal tidak tersedia untuk dibuatkan pengganti.'
                );
            }
            $noOrder = $this->orderNumbers->next('outdoor', $data['TglOrder']);

            $order = OrderOutdoor::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'created_by' => auth()->id(),
                'Cetak' => false,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
                'replacement_order_id' => $replacement?->id,
                'replacement_credit' => $replacement ? (float) $replacement->jumlah_dibayar : 0,
                'payment_queue' => $replacement ? 'kasir' : ($data['payment_queue'] ?? 'kasir'),
                'sent_to_cs_at' => ! $replacement && ($data['payment_queue'] ?? 'kasir') === 'cs' ? now() : null,
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalOutdoor($order->fresh())]);
            $this->pricingService->snapshotLinePrices('outdoor', $order);
        }, attempts: 3);

        return redirect()->route($replacement ? 'kasir.index' : 'order-outdoor.index')
            ->with('status', $replacement
                ? 'Nota pengganti berhasil dibuat dan siap diproses kasir.'
                : (($data['payment_queue'] ?? 'kasir') === 'cs' ? 'Order outdoor berhasil dikirim ke Customer Service.' : 'Order outdoor berhasil dikirim ke Kasir.'));
    }

    public function edit(OrderOutdoor $orderOutdoor): View
    {
        return view('order-outdoor.edit', [
            'order' => $orderOutdoor,
            'items' => $orderOutdoor->items,
            'selectedCustomer' => $orderOutdoor->customer,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderOutdoorRequest $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderOutdoor) {
            $orderOutdoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
            ]);

            $orderOutdoor->items()->delete();

            $this->saveItems($orderOutdoor, $data['items']);

            $orderOutdoor->update(['total' => $this->pricingService->totalOutdoor($orderOutdoor->fresh())]);
            $this->pricingService->snapshotLinePrices('outdoor', $orderOutdoor);
        });

        return redirect()->route('order-outdoor.index')->with('status', 'Order outdoor berhasil diperbarui.');
    }

    public function destroy(OrderOutdoor $orderOutdoor): RedirectResponse
    {
        $orderOutdoor->delete();

        return redirect()->route('order-outdoor.index')->with('status', 'Order outdoor berhasil dihapus.');
    }

    /**
     * Operator/staff working the cetak queue flags an order for
     * cancellation. This doesn't cancel it yet — it just freezes the order
     * (no further "Update Status" progress) until Admin/Admin Kasir
     * approves or rejects the request.
     */
    public function requestCancel(Request $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if ($orderOutdoor->cancel_requested_at) {
            return back()->with('error', 'Order ini sudah punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($orderOutdoor, $data) {
            $orderOutdoor = $orderOutdoor->newQuery()->lockForUpdate()->findOrFail($orderOutdoor->id);
            abort_if($orderOutdoor->status === 'batal' || $orderOutdoor->cancel_requested_at, 422, 'Order batal atau sudah punya pengajuan pembatalan.');
            $orderOutdoor->update(['cancel_requested_at' => now(), 'cancel_requested_by' => auth()->id(), 'cancel_reason' => $data['cancel_reason']]);
            OrderStatusNote::create([
                'order_type' => 'outdoor', 'order_id' => $orderOutdoor->id, 'stage' => 'pembatalan',
                'action' => 'diajukan', 'catatan' => $data['cancel_reason'], 'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }, attempts: 3);

        $this->notifier->notify(
            'order-outdoor.approve-cancel',
            "Pengajuan pembatalan order outdoor {$orderOutdoor->NoOrder}"
                .($orderOutdoor->customer?->NmCust ? ' ('.ucwords(mb_strtolower($orderOutdoor->customer->NmCust)).')' : '')
                ." — alasan: {$data['cancel_reason']}. Menunggu persetujuan."
        );

        return back()->with('status', 'Pengajuan pembatalan order dikirim, menunggu persetujuan Admin/Admin Kasir.');
    }

    /**
     * Admin/Admin Kasir approves a pending cancellation with one of two
     * outcomes: void the invoice and queue it for a replacement note
     * (nota_pengganti), or cancel the order outright with nothing further
     * to process (batal_total). invoice_voided_at is the flag that decides
     * which — it's what Kasir's "needs replacement" queue and
     * createReplacement() both key off, so leaving it null for batal_total
     * naturally keeps that order out of the replacement flow.
     */
    public function approveCancel(Request $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if (! $orderOutdoor->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'resolution' => ['required', 'in:nota_pengganti,batal_total'],
        ]);

        $isReplacement = $data['resolution'] === 'nota_pengganti';

        abort_if(
            $isReplacement && $orderOutdoor->status_bayar === 'belum_bayar',
            422,
            'Pre-order yang belum dibayar hanya dapat dibatalkan total.'
        );

        $cancelNote = '';

        DB::transaction(function () use ($orderOutdoor, $isReplacement, $request, &$cancelNote) {
            $orderOutdoor = $orderOutdoor->newQuery()->lockForUpdate()->findOrFail($orderOutdoor->id);
            abort_if($orderOutdoor->status === 'batal' || ! $orderOutdoor->cancel_requested_at, 422, 'Pembatalan sudah diproses atau pengajuan sudah berubah.');
            $orderOutdoor->update([
                'cancel_approved_at' => now(),
                'cancel_approved_by' => auth()->id(),
                'invoice_voided_at' => $isReplacement ? now() : null,
                'status' => 'batal',
            ]);

            if (! $isReplacement) {
                $cancelNote = app(OrderCancellationRefund::class)->process(
                    $orderOutdoor, 'outdoor', OrderCancellationRefund::validated($request->all(), $orderOutdoor)
                );
            }

            OrderStatusNote::create([
                'order_type' => 'outdoor',
                'order_id' => $orderOutdoor->id,
                'stage' => 'pembatalan',
                'action' => 'disetujui',
                'catatan' => $isReplacement
                    ? 'Nota dihanguskan; menunggu pembuatan nota pengganti oleh kasir.'
                    : trim('Disetujui batal total, tidak ada nota pengganti. '.$cancelNote),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        }, attempts: 3);

        if ($isReplacement) {
            return redirect()->route('kasir.replacement.create', $orderOutdoor)
                ->with('status', 'Pembatalan disetujui. Nota lama hangus, silakan buat nota pengganti.');
        }

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', trim('Pembatalan disetujui, order dibatalkan total. '.$cancelNote));
    }

    public function rejectCancel(OrderOutdoor $orderOutdoor): RedirectResponse
    {
        DB::transaction(function () use ($orderOutdoor) {
            $orderOutdoor = $orderOutdoor->newQuery()->lockForUpdate()->findOrFail($orderOutdoor->id);
            abort_if($orderOutdoor->status === 'batal' || ! $orderOutdoor->cancel_requested_at, 422, 'Pembatalan sudah diproses atau pengajuan sudah berubah.');

            $orderOutdoor->update(['cancel_requested_at' => null, 'cancel_requested_by' => null, 'cancel_reason' => null]);
            OrderStatusNote::create([
                'order_type' => 'outdoor', 'order_id' => $orderOutdoor->id, 'stage' => 'pembatalan',
                'action' => 'ditolak', 'catatan' => null, 'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('order-desain.index', ['tab' => 'outdoor'])->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderOutdoor $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            OrderOutdoorDetail::create([
                'order_outdoor_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'NmFile' => $item['NmFile'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'qty_desain' => $item['Qty'],
                'KdCtk' => $item['KdCtk'] ?? null,
                'ada_finishing' => isset($item['ada_finishing']) ? $item['ada_finishing'] === 'ya' : null,
                'jenis_finishing' => $item['jenis_finishing'] ?? null,
            ]);
        }
    }

}
