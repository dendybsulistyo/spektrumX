<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderIndoorRequest;
use App\Models\Customer;
use App\Models\HargaArtwork;
use App\Models\Kategori;
use App\Models\KonfigurasiJasaPotong;
use App\Models\KonfigurasiJasaPotongArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use App\Models\OrderStatusNote;
use App\Models\Produk;
use App\Services\ApproverNotificationService;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderIndoorController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
        private readonly ApproverNotificationService $notifier,
    ) {}

    public function index(Request $request): View
    {
        $orders = OrderIndoor::query()
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

        return view('order-indoor.index', compact('orders'));
    }

    public function create(): View
    {
        return view('order-indoor.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : null,
            'produkList' => Produk::orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::orderBy('NoUrut')->get(),
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
            'produkList' => Produk::orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::orderBy('NoUrut')->get(),
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

            $noOrder = $this->generateNoOrder($data['TglOrder']);

            $order = OrderIndoor::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'created_by' => auth()->id(),
                'Cetak' => 0,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
                'created_at' => now(),
                'replacement_order_id' => $replacement?->id,
                'replacement_credit' => $replacement ? (float) $replacement->jumlah_dibayar : 0,
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalIndoor($order)]);
        });

        return redirect()->route($replacement ? 'kasir.index' : 'order-indoor.index')
            ->with('status', $replacement ? 'Nota pengganti berhasil dibuat dan siap diproses kasir.' : 'Order berhasil dibuat.');
    }

    public function edit(OrderIndoor $orderIndoor): View
    {
        $items = OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->get();

        return view('order-indoor.edit', [
            'order' => $orderIndoor,
            'items' => $items,
            'selectedCustomer' => $orderIndoor->customer,
            'produkList' => Produk::orderBy('NoUrut')->get(),
            'artworkProdukList' => HargaArtwork::orderBy('NoUrut')->get(),
            'kategoriList' => $this->kategoriGabungan(),
            'nilaiX' => KonfigurasiJasaPotong::current()->nilai_x,
            'nilaiXArtwork' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
        ]);
    }

    public function update(StoreOrderIndoorRequest $request, OrderIndoor $orderIndoor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderIndoor) {
            $orderIndoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
            ]);

            OrderIndoorDetail::where('BrsOrder', 'like', $orderIndoor->NoOrder.'%')->delete();

            $this->saveItems($orderIndoor, $data['items']);

            $orderIndoor->update(['total' => $this->pricingService->totalIndoor($orderIndoor)]);
        });

        return redirect()->route('order-indoor.index')->with('status', 'Order berhasil diperbarui.');
    }

    public function destroy(OrderIndoor $orderIndoor): RedirectResponse
    {
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

        $orderIndoor->update([
            'cancel_requested_at' => now(),
            'cancel_requested_by' => auth()->id(),
            'cancel_reason' => $data['cancel_reason'],
        ]);

        OrderStatusNote::create([
            'order_type' => 'indoor',
            'order_id' => $orderIndoor->id,
            'stage' => 'pembatalan',
            'action' => 'diajukan',
            'catatan' => $data['cancel_reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->notifier->notify(
            'order-indoor.approve-cancel',
            "Pengajuan pembatalan order indoor {$orderIndoor->NoOrder}"
                .($orderIndoor->customer?->NmCust ? ' ('.ucwords(mb_strtolower($orderIndoor->customer->NmCust)).')' : '')
                ." — alasan: {$data['cancel_reason']}. Menunggu persetujuan."
        );

        return redirect()->route('order-desain.index', ['tab' => 'indoor'])->with('status', 'Pengajuan pembatalan order dikirim, menunggu persetujuan Admin/Admin Kasir.');
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

        DB::transaction(function () use ($orderIndoor, $isReplacement) {
            $orderIndoor->update([
                'cancel_approved_at' => now(),
                'cancel_approved_by' => auth()->id(),
                'invoice_voided_at' => $isReplacement ? now() : null,
                'status' => 'batal',
            ]);

            OrderStatusNote::create([
                'order_type' => 'indoor',
                'order_id' => $orderIndoor->id,
                'stage' => 'pembatalan',
                'action' => 'disetujui',
                'catatan' => $isReplacement
                    ? 'Nota dihanguskan; menunggu pembuatan nota pengganti oleh kasir.'
                    : 'Disetujui batal total, tidak ada nota pengganti.',
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        if ($isReplacement) {
            return redirect()->route('kasir.replacement.create.indoor', $orderIndoor)
                ->with('status', 'Pembatalan disetujui. Nota lama hangus, silakan buat nota pengganti.');
        }

        return redirect()->route('order-desain.index', ['tab' => 'indoor'])->with('status', 'Pembatalan disetujui, order dibatalkan total.');
    }

    public function rejectCancel(OrderIndoor $orderIndoor): RedirectResponse
    {
        if (! $orderIndoor->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $orderIndoor->update([
            'cancel_requested_at' => null,
            'cancel_requested_by' => null,
            'cancel_reason' => null,
        ]);

        OrderStatusNote::create([
            'order_type' => 'indoor',
            'order_id' => $orderIndoor->id,
            'stage' => 'pembatalan',
            'action' => 'ditolak',
            'catatan' => null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-desain.index', ['tab' => 'indoor'])->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderIndoor $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            $jenisProduk = $item['jenis_produk'] ?? 'indoor';
            $nmProd = $jenisProduk === 'artwork'
                ? HargaArtwork::where('KdProd', $item['KdProd'])->value('NmProd')
                : Produk::where('KdProd', $item['KdProd'])->value('NmProd');

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
                'qty_desain' => $item['Qty'],
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

    private function generateNoOrder(string $tglOrder): string
    {
        $prefix = 'IND'.date('ymd', strtotime($tglOrder));

        $last = OrderIndoor::where('NoOrder', 'like', $prefix.'%')
            ->orderByDesc('NoOrder')
            ->value('NoOrder');

        $nextSeq = $last ? ((int) substr($last, 9, 5)) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
