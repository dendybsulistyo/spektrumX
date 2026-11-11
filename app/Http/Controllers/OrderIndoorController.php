<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderIndoorRequest;
use App\Models\Customer;
use App\Models\CustomerServiceJobSheet;
use App\Models\HargaArtwork;
use App\Models\Kategori;
use App\Models\KonfigurasiJasaPotong;
use App\Models\KonfigurasiJasaPotongArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use App\Models\OrderStatusNote;
use App\Models\Produk;
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

class OrderIndoorController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
        private readonly ApproverNotificationService $notifier,
        private readonly OrderNumberService $orderNumbers,
        private readonly OrderProofService $proofs,
    ) {}

    public function index(Request $request): View
    {
        $cancelledCount = OrderIndoor::query()->where('status', 'batal')->count();

        $orders = OrderIndoor::query()
            ->with('customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                // Nama customer dicari dulu di tabel customer (kecil), lalu order difilter per KdCust
                // supaya tidak ada subquery customer untuk setiap baris order.
                $customerCodes = Customer::query()->where('NmCust', 'like', "%{$search}%")->limit(200)->pluck('KdCust');
                $q->where(function ($searchQuery) use ($search, $customerCodes) {
                    $searchQuery->where('NoOrder', 'like', "%{$search}%")
                        ->orWhere('KdCust', 'like', "%{$search}%")
                        ->when($customerCodes->isNotEmpty(), fn ($query) => $query->orWhereIn('KdCust', $customerCodes));
                });
            })
            ->when($request->string('status')->toString() === 'batal', fn ($q) => $q->where('status', 'batal'))
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(15)
            ->withQueryString();

        return view('order-indoor.index', compact('orders', 'cancelledCount'));
    }

    public function create(Request $request): View
    {
        $jobSheet = $request->integer('job_sheet')
            ? CustomerServiceJobSheet::with('customer')->findOrFail($request->integer('job_sheet'))
            : null;
        $items = $jobSheet
            ? collect($jobSheet->items)->map(fn (array $item) => (object) [
                'KdProd' => '',
                'jenis_produk' => $item['order_type'] === 'AW' ? 'artwork' : 'indoor',
                'Judul' => mb_substr($jobSheet->folder_file ?: $item['material'], 0, 30),
                'Panjang' => $item['width'],
                'Lebar' => $item['height'],
                'Qty' => $item['quantity'],
                'PisauTurun' => null,
                'JumlahKertas' => null,
                'TebalKertas' => null,
            ])
            : null;
        // Order reguler dari proof yang sudah selesai di Layout (daftar Ctrl+X).
        $proof = $request->integer('proof') ? $this->proofs->convertible($request->integer('proof'), 'indoor') : null;
        if ($proof) {
            $items = $this->proofs->formItems($proof);
        }

        return view('order-indoor.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : ($proof?->customer ?? $jobSheet?->customer),
            'items' => $items,
            'sourceJobSheet' => $jobSheet,
            'sourceProof' => $proof,
            'produkList' => Produk::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'kategoriList' => $this->kategoriGabungan(),
            'nilaiX' => KonfigurasiJasaPotong::current()->nilai_x,
            'nilaiXArtwork' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
        ]);
    }

    /**
     * Pre-fills the normal create form with a cancelled+voided order's
     * customer/items so a kasir can issue its nota pengganti — the same
     * pattern as OrderOutdoorController::createReplacement().
     */
    public function createReplacement(OrderIndoor $orderIndoor): View
    {
        abort_unless(
            $orderIndoor->status === 'batal' && $orderIndoor->invoice_voided_at && ! $orderIndoor->replacement()->exists(),
            404
        );

        return view('order-indoor.create', [
            'replacementOrder' => $orderIndoor,
            'selectedCustomer' => $orderIndoor->customer,
            'produkList' => Produk::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'kategoriList' => $this->kategoriGabungan(),
            'nilaiX' => KonfigurasiJasaPotong::current()->nilai_x,
            'nilaiXArtwork' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
            'items' => $orderIndoor->detailItems(),
        ]);
    }

    public function store(StoreOrderIndoorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $replacement = null;

        DB::transaction(function () use ($data, &$replacement) {
            if (! empty($data['replacement_order_id'])) {
                $replacement = OrderIndoor::lockForUpdate()->findOrFail($data['replacement_order_id']);
                abort_unless(
                    $replacement->status === 'batal' && $replacement->invoice_voided_at && ! $replacement->replacement()->exists(),
                    422,
                    'Nota asal tidak tersedia untuk dibuatkan pengganti.'
                );
            }

            $noOrder = $this->orderNumbers->next('indoor', $data['TglOrder']);

            $order = OrderIndoor::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'estimasi_hari' => $this->estimasiHari($data, empty($data['job_sheet_id']) ? null : CustomerServiceJobSheet::find($data['job_sheet_id'])),
                'created_by' => auth()->id(),
                'Cetak' => 0,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
                'created_at' => now(),
                'replacement_order_id' => $replacement?->id,
                'replacement_credit' => $replacement ? (float) $replacement->jumlah_dibayar : 0,
                'payment_queue' => $replacement ? 'kasir' : ($data['payment_queue'] ?? 'kasir'),
                'sent_to_cs_at' => ! $replacement && ($data['payment_queue'] ?? 'kasir') === 'cs' ? now() : null,
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalIndoor($order)]);
            $this->pricingService->snapshotLinePrices('indoor', $order);

            if (! $replacement && ! empty($data['proof_id'])) {
                $this->proofs->markConverted((int) $data['proof_id'], 'indoor', $order);
            }

            // Catat nomor order ke Lembar Kerja CS asalnya (untuk riwayat CS).
            if (! empty($data['job_sheet_id'])) {
                CustomerServiceJobSheet::whereKey($data['job_sheet_id'])
                    ->whereNotNull('claimed_at')->where('claimed_order_type', 'indoor')->whereNull('order_id')
                    ->update(['order_id' => $order->id, 'order_number' => $order->NoOrder]);
            }
        }, attempts: 3);

        return redirect()->route($replacement ? 'kasir.index' : 'order-indoor.index')
            ->with('status', $replacement
                ? 'Nota pengganti berhasil dibuat dan siap diproses kasir.'
                : (($data['payment_queue'] ?? 'kasir') === 'cs' ? 'Order berhasil dikirim ke Customer Service.' : 'Order berhasil dikirim ke Kasir.'));
    }

    public function edit(OrderIndoor $orderIndoor): View|RedirectResponse
    {
        if ($reason = $orderIndoor->editBlockReason()) {
            return redirect()->route('order-indoor.index')->with('error', 'Order '.$orderIndoor->NoOrder.' tidak dapat diubah. '.$reason);
        }

        $items = OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->get();

        return view('order-indoor.edit', [
            'order' => $orderIndoor,
            'items' => $items,
            'selectedCustomer' => $orderIndoor->customer,
            'produkList' => Produk::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::with('hargaBertingkat')->orderBy('NoUrut')->get(),
            'kategoriList' => $this->kategoriGabungan(),
            'nilaiX' => KonfigurasiJasaPotong::current()->nilai_x,
            'nilaiXArtwork' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
        ]);
    }

    public function update(StoreOrderIndoorRequest $request, OrderIndoor $orderIndoor): RedirectResponse
    {
        if ($reason = $orderIndoor->editBlockReason()) {
            return redirect()->route('order-indoor.index')->with('error', 'Order '.$orderIndoor->NoOrder.' tidak dapat diubah. '.$reason);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $orderIndoor) {
            $orderIndoor->forgetSalesOrderPrint();

            $orderIndoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
                'estimasi_hari' => $this->estimasiHari($data, $orderIndoor->customerServiceJobSheet),
            ]);

            OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->delete();

            $this->saveItems($orderIndoor, $data['items']);

            $orderIndoor->update(['total' => $this->pricingService->totalIndoor($orderIndoor)]);
            $this->pricingService->snapshotLinePrices('indoor', $orderIndoor);
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil diperbarui.');
    }

    public function destroy(OrderIndoor $orderIndoor): RedirectResponse
    {
        if ($reason = $orderIndoor->deleteBlockReason()) {
            return back()->with('error', 'Order '.$orderIndoor->NoOrder.' tidak dapat dihapus. '.$reason);
        }

        DB::transaction(function () use ($orderIndoor) {
            OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->delete();
            $orderIndoor->delete();
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil dihapus.');
    }

    public function requestCancel(Request $request, OrderIndoor $orderIndoor): RedirectResponse
    {
        if ($orderIndoor->cancel_requested_at) {
            return back()->with('error', 'Order ini sudah punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($orderIndoor, $data) {
            $orderIndoor = $orderIndoor->newQuery()->lockForUpdate()->findOrFail($orderIndoor->id);
            abort_if($orderIndoor->status === 'batal' || $orderIndoor->cancel_requested_at, 422, 'Order batal atau sudah punya pengajuan pembatalan.');
            $orderIndoor->update(['cancel_requested_at' => now(), 'cancel_requested_by' => auth()->id(), 'cancel_reason' => $data['cancel_reason']]);
            OrderStatusNote::create([
                'order_type' => 'indoor', 'order_id' => $orderIndoor->id, 'stage' => 'pembatalan',
                'action' => 'diajukan', 'catatan' => $data['cancel_reason'], 'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }, attempts: 3);

        $this->notifier->notify(
            'order-indoor.approve-cancel',
            "Pengajuan pembatalan order indoor {$orderIndoor->NoOrder}"
                .($orderIndoor->customer?->NmCust ? ' ('.ucwords(mb_strtolower($orderIndoor->customer->NmCust)).')' : '')
                ." — alasan: {$data['cancel_reason']}. Menunggu persetujuan."
        );

        return back()->with('status', 'Pengajuan pembatalan order dikirim, menunggu persetujuan Admin/Admin Kasir.');
    }

    /**
     * Same two outcomes as Outdoor's: void the invoice and queue it for a
     * replacement note (nota_pengganti), or cancel outright (batal_total).
     */
    public function approveCancel(Request $request, OrderIndoor $orderIndoor): RedirectResponse
    {
        if (! $orderIndoor->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'resolution' => ['required', 'in:nota_pengganti,batal_total'],
        ]);

        $isReplacement = $data['resolution'] === 'nota_pengganti';

        // Nota pengganti tidak menghapus piutang lama; order hutang VIP harus batal total.
        abort_if(
            $isReplacement && $orderIndoor->status_bayar === 'hutang',
            422,
            'Order hutang VIP hanya dapat dibatalkan total (piutang dihapus, plafon dikembalikan).'
        );

        abort_if(
            $isReplacement && $orderIndoor->status_bayar === 'belum_bayar',
            422,
            'Pre-order yang belum dibayar hanya dapat dibatalkan total.'
        );

        $cancelNote = '';

        DB::transaction(function () use ($orderIndoor, $isReplacement, $request, &$cancelNote) {
            $orderIndoor = $orderIndoor->newQuery()->lockForUpdate()->findOrFail($orderIndoor->id);
            abort_if($orderIndoor->status === 'batal' || ! $orderIndoor->cancel_requested_at, 422, 'Pembatalan sudah diproses atau pengajuan sudah berubah.');
            $orderIndoor->update([
                'cancel_approved_at' => now(),
                'cancel_approved_by' => auth()->id(),
                'invoice_voided_at' => $isReplacement ? now() : null,
                'status' => 'batal',
            ]);

            if (! $isReplacement) {
                $cancelNote = app(OrderCancellationRefund::class)->process(
                    $orderIndoor, 'indoor', OrderCancellationRefund::validated($request->all(), $orderIndoor)
                );
            }

            OrderStatusNote::create([
                'order_type' => 'indoor',
                'order_id' => $orderIndoor->id,
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
            return redirect()->route('kasir.replacement.create.indoor', $orderIndoor)
                ->with('status', 'Pembatalan disetujui. Nota lama hangus, silakan buat nota pengganti.');
        }

        return back()->with('status', trim('Pembatalan disetujui, order dibatalkan total. '.$cancelNote));
    }

    public function rejectCancel(OrderIndoor $orderIndoor): RedirectResponse
    {
        DB::transaction(function () use ($orderIndoor) {
            $orderIndoor = $orderIndoor->newQuery()->lockForUpdate()->findOrFail($orderIndoor->id);
            abort_if($orderIndoor->status === 'batal' || ! $orderIndoor->cancel_requested_at, 422, 'Pembatalan sudah diproses atau pengajuan sudah berubah.');

            $orderIndoor->update(['cancel_requested_at' => null, 'cancel_requested_by' => null, 'cancel_reason' => null]);
            OrderStatusNote::create([
                'order_type' => 'indoor', 'order_id' => $orderIndoor->id, 'stage' => 'pembatalan',
                'action' => 'ditolak', 'catatan' => null, 'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }, attempts: 3);

        return back()->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderIndoor $order, array $items): void
    {
        $itemsByType = collect($items)->groupBy(fn (array $item) => $item['jenis_produk'] ?? 'indoor');
        $indoorProducts = Produk::query()
            ->whereIn('KdProd', $itemsByType->get('indoor', collect())->pluck('KdProd')->unique())
            ->get(['KdProd', 'NmProd', 'KdDivs', 'diproses'])->keyBy('KdProd');
        $artworkProducts = HargaArtwork::query()
            ->whereIn('KdProd', $itemsByType->get('artwork', collect())->pluck('KdProd')->unique())
            ->get(['KdProd', 'NmProd', 'KdDivs', 'diproses'])->keyBy('KdProd');
        // Kategori barang jadi (mis. stand banner): tidak lewat Layout & Cetak.
        $skipLayoutCetakDivisions = Schema::hasColumn('kategori_produk_indoor', 'lewati_layout_cetak')
            ? Kategori::query()->where('lewati_layout_cetak', true)->pluck('KdDivs')->all()
            : [];

        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            $jenisProduk = $item['jenis_produk'] ?? 'indoor';
            $product = $jenisProduk === 'artwork'
                ? $artworkProducts->get($item['KdProd'])
                : $indoorProducts->get($item['KdProd']);
            $nmProd = $product?->NmProd;
            // Produk tidak diproses operator: langsung selesai sejak SO dibuat.
            $tanpaProses = $product?->diproses === false;
            $division = $product?->KdDivs ?: substr((string) $item['KdProd'], 0, 2);
            $mulaiFinishing = ! $tanpaProses && in_array($division, $skipLayoutCetakDivisions, true);

            OrderIndoorDetail::create([
                'order_indoor_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'KdProd' => $item['KdProd'],
                'jenis_produk' => $jenisProduk,
                'NmProd' => $nmProd ?? '',
                'Judul' => $item['Judul'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'qty_desain' => $tanpaProses || $mulaiFinishing ? 0 : $item['Qty'],
                'qty_finishing' => $mulaiFinishing ? $item['Qty'] : 0,
                'qty_selesai' => $tanpaProses ? $item['Qty'] : 0,
                'tanpa_proses' => $tanpaProses,
                'KdStat' => 0,
                'PisauTurun' => $item['PisauTurun'] ?? null,
                'JumlahKertas' => $item['JumlahKertas'] ?? null,
                'TebalKertas' => $item['TebalKertas'] ?? null,
            ]);
        }
    }

    /**
     * Kategori shared by both catalogs (Indoor's produk_indoor and
     * Artwork's harga_artwork both key off kategori_produk_indoor.KdDivs —
     * see Kategori::produk()/produkArtwork()), so the merged order form's
     * "pick by kategori" picker can browse either catalog from one list.
     */
    private function kategoriGabungan()
    {
        return Kategori::where(fn ($q) => $q->whereHas('produk')->orWhereHas('produkArtwork'))
            ->orderBy('NoUrut')
            ->get();
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
