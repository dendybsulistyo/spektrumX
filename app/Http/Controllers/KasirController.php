<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Services\CustomerCreditService;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KasirController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly CustomerCreditService $creditService) {}

    public function index(): View
    {
        $indoorOrders = OrderIndoor::query()
            ->with('customer')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        $outdoorOrders = OrderOutdoor::query()
            ->with('customer')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        $artworkOrders = OrderArtwork::query()
            ->with('customer')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        // Outdoor orders paid via DP still owe a balance — surfaced here so
        // kasir can record the pelunasan (settlement) whenever it comes in.
        $dpOrders = OrderOutdoor::query()
            ->with('customer')
            ->where('status_bayar', 'dp')
            ->where('jumlah_piutang', '>', 0)
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        return view('kasir.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'dpOrders'));
    }

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer.limit');

        $items = $type === 'indoor' ? $order->detailItems() : $order->items;

        return view('kasir.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }

    public function bayar(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        $data = $request->validate([
            'metode_bayar' => ['required', 'in:tunai,hutang,dp'],
            'jumlah_dp' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->load('customer.limit');
        $total = (float) $order->total;

        if ($data['metode_bayar'] === 'hutang') {
            if (! $this->creditService->canTakeHutang($order->customer, $total)) {
                return back()->with('error', 'Customer tidak bisa hutang: bukan VIP atau melebihi sisa limit piutang.');
            }
        }

        if ($data['metode_bayar'] === 'dp') {
            // DP is an Outdoor-only facility — the goods still get produced
            // and handed over, but the balance must be settled (via lunasi())
            // before Pengambilan will release them to the customer.
            if ($type !== 'outdoor') {
                return back()->with('error', 'DP hanya berlaku untuk order outdoor.');
            }

            $jumlahDp = (float) ($data['jumlah_dp'] ?? 0);
            $minimumDp = $total * 0.5;

            if ($jumlahDp < $minimumDp) {
                return back()->with('error', 'DP minimal 50% dari total order (Rp '.number_format($minimumDp, 0, ',', '.').').');
            }

            if ($jumlahDp >= $total) {
                return back()->with('error', 'Jumlah DP tidak boleh sama dengan atau melebihi total order — gunakan pembayaran tunai (lunas) sebagai gantinya.');
            }
        }

        DB::transaction(function () use ($order, $type, $data, $total) {
            match ($data['metode_bayar']) {
                'hutang' => (function () use ($order, $total) {
                    $this->creditService->addHutang($order->customer, $total);

                    $order->update([
                        'status_bayar' => 'hutang',
                        'metode_bayar' => 'hutang',
                        'jumlah_dibayar' => 0,
                        'jumlah_piutang' => $total,
                    ]);
                })(),
                'dp' => (function () use ($order, $data, $total) {
                    $jumlahDp = (float) $data['jumlah_dp'];

                    $order->update([
                        'status_bayar' => 'dp',
                        'metode_bayar' => 'dp',
                        'jumlah_dibayar' => $jumlahDp,
                        'jumlah_piutang' => $total - $jumlahDp,
                    ]);
                })(),
                default => $order->update([
                    'status_bayar' => 'lunas',
                    'metode_bayar' => 'tunai',
                    'jumlah_dibayar' => $total,
                    'jumlah_piutang' => 0,
                ]),
            };

            $order->update([
                'kasir_user_id' => auth()->id(),
                'dibayar_at' => now(),
                'status' => 'desain',
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => $data['catatan'] ?? null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Pembayaran berhasil diproses.')
            ->with('autoPrintInvoice', true);
    }

    /**
     * Settle the remaining balance of a DP order. Can happen any time after
     * the DP itself — the only hard gate is that Pengambilan won't release
     * goods until this has been done.
     */
    public function lunasi(string $type, int $id): RedirectResponse
    {
        if ($type !== 'outdoor') {
            abort(404);
        }

        $order = OrderOutdoor::findOrFail($id);

        if ($order->status_bayar !== 'dp' || (float) $order->jumlah_piutang <= 0) {
            return back()->with('error', 'Order ini tidak sedang menunggu pelunasan DP.');
        }

        DB::transaction(function () use ($order, $type) {
            $order->update([
                'status_bayar' => 'lunas',
                'jumlah_dibayar' => $order->total,
                'jumlah_piutang' => 0,
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => 'Pelunasan sisa DP',
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('kasir.index')->with('status', 'Sisa DP berhasil dilunasi.');
    }
}
