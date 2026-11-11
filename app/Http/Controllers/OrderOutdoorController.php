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
use App\Services\ApproverNotificationService;
use App\Services\OrderCancellationRefund;
use App\Services\OrderNumberService;
use App\Services\OrderPricingService;
use App\Services\OrderProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OrderOutdoorController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
        private readonly ApproverNotificationService $notifier,
        private readonly OrderNumberService $orderNumbers,
        private readonly OrderProofService $proofs,
    ) {}

    public function index(Request $request): View
    {
        $cancelledCount = OrderOutdoor::query()->where('status', 'batal')->count();

        $orders = OrderOutdoor::query()
            ->with('customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('NoOrder', 'like', "%{$search}%")
                        ->orWhere('KdCust', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString() === 'batal', fn ($q) => $q->where('status', 'batal'))
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(15)
            ->withQueryString();

        $comments = OrderComment::with('user')
            ->where('order_type', 'outdoor')->whereIn('order_id', $orders->pluck('id'))
            ->orderBy('created_at')->get()->groupBy('order_id');

        $unread = OrderComment::unreadCountsFor('outdoor', $orders->pluck('id'));

        return view('order-outdoor.index', compact('orders', 'comments', 'unread', 'cancelledCount'));
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
        // Order reguler dari proof yang sudah selesai di Layout (daftar Ctrl+X).
        $proof = $request->integer('proof') ? $this->proofs->convertible($request->integer('proof'), 'outdoor') : null;
        if ($proof) {
            $items = $this->proofs->formItems($proof);
        }

        return view('order-outdoor.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : ($proof?->customer ?? $jobSheet?->customer),
            'items' => $items,
            'sourceJobSheet' => $jobSheet,
            'sourceProof' => $proof,
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
                'estimasi_hari' => $this->estimasiHari($data, empty($data['job_sheet_id']) ? null : CustomerServiceJobSheet::find($data['job_sheet_id'])),
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

            if (! $replacement && ! empty($data['proof_id'])) {
                $this->proofs->markConverted((int) $data['proof_id'], 'outdoor', $order);
            }

            // Catat nomor order ke Lembar Kerja CS asalnya (untuk riwayat CS).
            if (! empty($data['job_sheet_id'])) {
                CustomerServiceJobSheet::whereKey($data['job_sheet_id'])
                    ->whereNotNull('claimed_at')->where('claimed_order_type', 'outdoor')->whereNull('order_id')
                    ->update(['order_id' => $order->id, 'order_number' => $order->NoOrder]);
            }
        }, attempts: 3);

        return redirect()->route($replacement ? 'kasir.index' : 'order-outdoor.index')
            ->with('status', $replacement
                ? 'Nota pengganti berhasil dibuat dan siap diproses kasir.'
                : (($data['payment_queue'] ?? 'kasir') === 'cs' ? 'Order outdoor berhasil dikirim ke Customer Service.' : 'Order outdoor berhasil dikirim ke Kasir.'));
    }

    public function edit(OrderOutdoor $orderOutdoor): View|RedirectResponse
    {
        if ($reason = $orderOutdoor->editBlockReason()) {
            return redirect()->route('order-outdoor.index')->with('error', 'Order '.$orderOutdoor->NoOrder.' tidak dapat diubah. '.$reason);
        }

        return view('order-outdoor.edit', [
            'order' => $orderOutdoor,
            'items' => $orderOutdoor->items->reject->is_proof->values(),
            'selectedCustomer' => $orderOutdoor->customer,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderOutdoorRequest $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if ($reason = $orderOutdoor->editBlockReason()) {
            return redirect()->route('order-outdoor.index')->with('error', 'Order '.$orderOutdoor->NoOrder.' tidak dapat diubah. '.$reason);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $orderOutdoor) {
            $orderOutdoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
                'estimasi_hari' => $this->estimasiHari($data, $orderOutdoor->customerServiceJobSheet),
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
        if ($reason = $orderOutdoor->deleteBlockReason()) {
            return back()->with('error', 'Order '.$orderOutdoor->NoOrder.' tidak dapat dihapus. '.$reason);
        }

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

        // Nota pengganti tidak menghapus piutang lama; order hutang VIP harus batal total.
        abort_if(
            $isReplacement && $orderOutdoor->status_bayar === 'hutang',
            422,
            'Order hutang VIP hanya dapat dibatalkan total (piutang dihapus, plafon dikembalikan).'
        );

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

        return back()->with('status', trim('Pembatalan disetujui, order dibatalkan total. '.$cancelNote));
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

        return back()->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * Item dengan Proof = Ya otomatis mendapat satu baris proof ukuran mini
     * (qty 1, Rp 0) yang ikut alur produksi bersama item aslinya.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderOutdoor $order, array $items): void
    {
        $sequence = 0;
        // Bahan barang jadi (mis. laminasi): tidak lewat Layout & Cetak, langsung Finishing.
        $skipLayoutCetakBahan = Schema::hasColumn('bahan_cetak_outdoor', 'lewati_layout_cetak')
            ? BahanCetakOutdoor::query()->where('lewati_layout_cetak', true)->whereNotNull('NoCetak')->pluck('NoCetak')->all()
            : [];

        foreach ($items as $item) {
            $withProof = ($item['ada_finishing'] ?? null) === 'ya';
            $mulaiFinishing = in_array(substr((string) ($item['KdCtk'] ?? ''), 2, 2), $skipLayoutCetakBahan, true);

            OrderOutdoorDetail::create([
                'order_outdoor_id' => $order->id,
                'BrsOrder' => $order->NoOrder.str_pad((string) ++$sequence, 2, '0', STR_PAD_LEFT),
                'NmFile' => $item['NmFile'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'qty_desain' => $mulaiFinishing ? 0 : $item['Qty'],
                'qty_finishing' => $mulaiFinishing ? $item['Qty'] : 0,
                'KdCtk' => $item['KdCtk'] ?? null,
                'ada_finishing' => isset($item['ada_finishing']) ? $withProof : null,
                'jenis_finishing' => $item['jenis_finishing'] ?? null,
            ]);

            if ($withProof) {
                OrderOutdoorDetail::create([
                    'order_outdoor_id' => $order->id,
                    'BrsOrder' => $order->NoOrder.str_pad((string) ++$sequence, 2, '0', STR_PAD_LEFT),
                    'NmFile' => mb_substr('PROOF MINI - '.$item['NmFile'], 0, 255),
                    'Panjang' => 0,
                    'Lebar' => 0,
                    'Qty' => 1,
                    'qty_desain' => $mulaiFinishing ? 0 : 1,
                    'qty_finishing' => $mulaiFinishing ? 1 : 0,
                    'KdCtk' => $item['KdCtk'] ?? null,
                    'is_proof' => true,
                ]);
            }
        }
    }

    /**
     * Estimasi (hari) isian Operator File; bila Lembar Kerja CS sudah punya
     * Progress Hari, angka CS yang dipakai dan tidak bisa diubah. Kosong = 1 hari.
     *
     * @param  array<string, mixed>  $data
     */
    private function estimasiHari(array $data, ?CustomerServiceJobSheet $jobSheet): ?int
    {
        return $jobSheet?->progress_hari ?: (isset($data['estimasi_hari']) ? (int) $data['estimasi_hari'] : null);
    }
}
