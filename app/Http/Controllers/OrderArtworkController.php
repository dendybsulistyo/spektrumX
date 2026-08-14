<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderArtworkRequest;
use App\Models\HargaArtwork;
use App\Models\Kategori;
use App\Models\KonfigurasiJasaPotongArtwork;
use App\Models\OrderArtwork;
use App\Models\OrderArtworkDetail;
use App\Models\OrderStatusNote;
use App\Services\ApproverNotificationService;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderArtworkController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricingService,
        private readonly ApproverNotificationService $notifier,
    ) {}

    public function index(Request $request): View
    {
        $orders = OrderArtwork::query()
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

        return view('order-artwork.index', compact('orders'));
    }

    /**
     * Order Artwork's own "new order" form is retired — new orders (Indoor,
     * Artwork, or a mix of both in one nota) are now all created through
     * Order Indoor's merged form. Old orders already in `order_artwork`
     * keep working through every other method on this controller
     * (edit/update/destroy/cancel/nota-pengganti) unchanged.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('order-indoor.create')
            ->with('status', 'Order artwork baru sekarang dibuat lewat form Order Indoor (bisa campur produk Indoor & Artwork dalam 1 nota).');
    }

    /**
     * Pre-fills the normal create form with a cancelled+voided order's
     * customer/items so a kasir can issue its nota pengganti — the same
     * pattern as OrderOutdoorController::createReplacement().
     */
    public function createReplacement(OrderArtwork $orderArtwork): View
    {
        abort_unless(
            $orderArtwork->status === 'batal' && $orderArtwork->invoice_voided_at && ! $orderArtwork->replacement()->exists(),
            404
        );

        return view('order-artwork.create', [
            'replacementOrder' => $orderArtwork,
            'selectedCustomer' => $orderArtwork->customer,
            'items' => $orderArtwork->items,
            'produkList' => HargaArtwork::orderBy('NoUrut')->get(),
            'kategoriList' => Kategori::whereHas('produkArtwork')->orderBy('NoUrut')->get(),
            'nilaiX' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
        ]);
    }

    public function store(StoreOrderArtworkRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $replacement = null;

        DB::transaction(function () use ($data, &$replacement) {
            if (! empty($data['replacement_order_id'])) {
                $replacement = OrderArtwork::lockForUpdate()->findOrFail($data['replacement_order_id']);
                abort_unless(
                    $replacement->status === 'batal' && $replacement->invoice_voided_at && ! $replacement->replacement()->exists(),
                    422,
                    'Nota asal tidak tersedia untuk dibuatkan pengganti.'
                );
            }

            $noOrder = $this->generateNoOrder($data['TglOrder']);

            $order = OrderArtwork::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'created_by' => auth()->id(),
                'Cetak' => false,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
                'replacement_order_id' => $replacement?->id,
                'replacement_credit' => $replacement ? (float) $replacement->jumlah_dibayar : 0,
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalArtwork($order->fresh('items'))]);
        });

        return redirect()->route($replacement ? 'kasir.index' : 'order-artwork.index')
            ->with('status', $replacement ? 'Nota pengganti berhasil dibuat dan siap diproses kasir.' : 'Order artwork berhasil dibuat.');
    }

    public function edit(OrderArtwork $orderArtwork): View
    {
        return view('order-artwork.edit', [
            'order' => $orderArtwork,
            'items' => $orderArtwork->items,
            'selectedCustomer' => $orderArtwork->customer,
            'produkList' => HargaArtwork::orderBy('NoUrut')->get(),
            'kategoriList' => Kategori::whereHas('produkArtwork')->orderBy('NoUrut')->get(),
            'nilaiX' => KonfigurasiJasaPotongArtwork::current()->nilai_x,
        ]);
    }

    public function update(StoreOrderArtworkRequest $request, OrderArtwork $orderArtwork): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderArtwork) {
            $orderArtwork->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
            ]);

            $orderArtwork->items()->delete();

            $this->saveItems($orderArtwork, $data['items']);

            $orderArtwork->update(['total' => $this->pricingService->totalArtwork($orderArtwork->fresh('items'))]);
        });

        return redirect()->route('order-artwork.index')->with('status', 'Order artwork berhasil diperbarui.');
    }

    public function destroy(OrderArtwork $orderArtwork): RedirectResponse
    {
        $orderArtwork->delete();

        return redirect()->route('order-artwork.index')->with('status', 'Order artwork berhasil dihapus.');
    }

    public function requestCancel(Request $request, OrderArtwork $orderArtwork): RedirectResponse
    {
        if ($orderArtwork->cancel_requested_at) {
            return back()->with('error', 'Order ini sudah punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $orderArtwork->update([
            'cancel_requested_at' => now(),
            'cancel_requested_by' => auth()->id(),
            'cancel_reason' => $data['cancel_reason'],
        ]);

        OrderStatusNote::create([
            'order_type' => 'artwork',
            'order_id' => $orderArtwork->id,
            'stage' => 'pembatalan',
            'action' => 'diajukan',
            'catatan' => $data['cancel_reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->notifier->notify(
            'order-artwork.approve-cancel',
            "Pengajuan pembatalan order artwork {$orderArtwork->NoOrder}"
                .($orderArtwork->customer?->NmCust ? ' ('.ucwords(mb_strtolower($orderArtwork->customer->NmCust)).')' : '')
                ." — alasan: {$data['cancel_reason']}. Menunggu persetujuan."
        );

        return redirect()->route('order-desain.index', ['tab' => 'artwork'])->with('status', 'Pengajuan pembatalan order dikirim, menunggu persetujuan Admin/Admin Kasir.');
    }

    /**
     * Same two outcomes as Outdoor's: void the invoice and queue it for a
     * replacement note (nota_pengganti), or cancel outright (batal_total).
     */
    public function approveCancel(Request $request, OrderArtwork $orderArtwork): RedirectResponse
    {
        if (! $orderArtwork->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'resolution' => ['required', 'in:nota_pengganti,batal_total'],
        ]);

        $isReplacement = $data['resolution'] === 'nota_pengganti';

        DB::transaction(function () use ($orderArtwork, $isReplacement) {
            $orderArtwork->update([
                'cancel_approved_at' => now(),
                'cancel_approved_by' => auth()->id(),
                'invoice_voided_at' => $isReplacement ? now() : null,
                'status' => 'batal',
            ]);

            OrderStatusNote::create([
                'order_type' => 'artwork',
                'order_id' => $orderArtwork->id,
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
            return redirect()->route('kasir.replacement.create.artwork', $orderArtwork)
                ->with('status', 'Pembatalan disetujui. Nota lama hangus, silakan buat nota pengganti.');
        }

        return redirect()->route('order-desain.index', ['tab' => 'artwork'])->with('status', 'Pembatalan disetujui, order dibatalkan total.');
    }

    public function rejectCancel(OrderArtwork $orderArtwork): RedirectResponse
    {
        if (! $orderArtwork->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $orderArtwork->update([
            'cancel_requested_at' => null,
            'cancel_requested_by' => null,
            'cancel_reason' => null,
        ]);

        OrderStatusNote::create([
            'order_type' => 'artwork',
            'order_id' => $orderArtwork->id,
            'stage' => 'pembatalan',
            'action' => 'ditolak',
            'catatan' => null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-desain.index', ['tab' => 'artwork'])->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderArtwork $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            $produk = HargaArtwork::where('KdProd', $item['KdProd'])->first();

            OrderArtworkDetail::create([
                'order_artwork_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'KdProd' => $item['KdProd'],
                'NmProd' => $produk->NmProd ?? '',
                'Judul' => $item['Judul'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'qty_desain' => $item['Qty'],
                'PisauTurun' => $item['PisauTurun'] ?? null,
                'JumlahKertas' => $item['JumlahKertas'] ?? null,
                'TebalKertas' => $item['TebalKertas'] ?? null,
            ]);
        }
    }

    private function generateNoOrder(string $tglOrder): string
    {
        $prefix = 'ART'.date('ymd', strtotime($tglOrder));

        $last = OrderArtwork::where('NoOrder', 'like', $prefix.'%')
            ->orderByDesc('NoOrder')
            ->value('NoOrder');

        $nextSeq = $last ? ((int) substr($last, 9, 5)) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
