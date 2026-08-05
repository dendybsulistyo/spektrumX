<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CustomerCreditService;
use App\Support\ResolvesOrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KasirController extends Controller
{
    use ResolvesOrderType;

    public function __construct(
        private readonly CustomerCreditService $creditService,
        private readonly AccountingService $accounting,
    ) {}

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

        // Voided invoices across all 3 order types, waiting for a kasir to
        // issue their nota pengganti — tagged with order_type so the view
        // can route each row to the right create-replacement page.
        $replacementOrders = collect();
        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $orderType => $model) {
            $model::query()
                ->with('customer')
                ->where('status', 'batal')
                ->whereNotNull('invoice_voided_at')
                ->doesntHave('replacement')
                ->orderByDesc('cancel_approved_at')
                ->get()
                ->each(function ($order) use (&$replacementOrders, $orderType) {
                    $order->order_type = $orderType;
                    $replacementOrders->push($order);
                });
        }
        $replacementOrders = $replacementOrders->sortByDesc('cancel_approved_at')->values();

        return view('kasir.index', compact('indoorOrders', 'outdoorOrders', 'artworkOrders', 'dpOrders', 'replacementOrders'));
    }

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load(['customer.limit', 'replaces']);

        $items = $type === 'indoor' ? $order->detailItems() : $order->items;

        return view('kasir.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }

    public function bayar(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        $data = $request->validate([
            'metode_bayar' => ['required', 'in:tunai,hutang,dp'],
            'cara_bayar' => ['required_if:metode_bayar,tunai,dp', 'nullable', 'in:tunai,qris,transfer'],
            'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
            'jumlah_dp' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $order->load('customer.limit');

        if ($order->diskonStatus() === 'pending') {
            return back()->with('error', 'Order ini sedang menunggu persetujuan diskon — tidak bisa diproses dulu.');
        }

        $total = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;

        // A replacement keeps the old invoice as history. Money actually
        // received on it becomes credit; its difference is the only cash
        // movement the cashier must record on the new invoice.
        if ($order->replacement_order_id) {
            if ($data['metode_bayar'] !== 'tunai') {
                return back()->with('error', 'Nota pengganti diproses tunai agar selisih tambahan atau cashback tercatat dengan jelas.');
            }

            $credit = (float) $order->replacement_credit;
            $topup = max($total - $credit, 0);
            $cashback = max($credit - $total, 0);

            DB::transaction(function () use ($order, $type, $data, $total, $topup, $cashback) {
                $order->update([
                    'status_bayar' => 'lunas',
                    'metode_bayar' => 'tunai',
                    'cara_bayar' => $data['cara_bayar'],
                    'no_referensi' => $data['no_referensi'] ?? null,
                    'jumlah_dibayar' => $total,
                    'jumlah_piutang' => 0,
                    'topup_amount' => $topup,
                    'cashback_amount' => $cashback,
                    'kasir_user_id' => auth()->id(),
                    'dibayar_at' => now(),
                    'status' => 'desain',
                ]);

                OrderStatusNote::create([
                    'order_type' => $type,
                    'order_id' => $order->id,
                    'stage' => 'kasir',
                    'action' => 'nota_pengganti',
                    'catatan' => ($cashback > 0
                        ? 'Cashback Rp '.number_format($cashback, 0, ',', '.')
                        : 'Tambahan pembayaran Rp '.number_format($topup, 0, ',', '.'))
                        .' — '.$this->caraBayarLabel($data['cara_bayar'], $data['no_referensi'] ?? null),
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                ]);

                if ($topup - $cashback !== 0.0) {
                    OrderPayment::create([
                        'order_type' => $type,
                        'order_id' => $order->id,
                        'jenis' => 'nota_pengganti',
                        'jumlah' => $topup - $cashback,
                        'cara_bayar' => $data['cara_bayar'],
                        'no_referensi' => $data['no_referensi'] ?? null,
                        'user_id' => auth()->id(),
                        'created_at' => now(),
                    ]);

                    $akunKas = AccountingService::akunKasFor($data['cara_bayar']);
                    $kdBantu = $order->customer?->KdCust ?? '';

                    $this->accounting->post(
                        now()->format('Y-m-d'),
                        $order->NoOrder,
                        'Nota pengganti '.$order->NoOrder,
                        $topup > 0
                            ? [
                                ['akun' => $akunKas, 'debet' => $topup, 'kd_bantu' => $kdBantu],
                                ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $topup],
                            ]
                            : [
                                ['akun' => AccountingService::AKUN_PENJUALAN, 'debet' => $cashback],
                                ['akun' => $akunKas, 'kredit' => $cashback, 'kd_bantu' => $kdBantu],
                            ]
                    );
                }
            });

            return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
                ->with('status', $cashback > 0 ? 'Nota pengganti selesai. Cashback telah dicatat.' : 'Nota pengganti selesai. Tambahan pembayaran telah dicatat.')
                ->with('autoPrintInvoice', true);
        }

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
                        'cara_bayar' => null,
                        'no_referensi' => null,
                        'jumlah_dibayar' => 0,
                        'jumlah_piutang' => $total,
                    ]);
                })(),
                'dp' => (function () use ($order, $data, $total) {
                    $jumlahDp = (float) $data['jumlah_dp'];

                    $order->update([
                        'status_bayar' => 'dp',
                        'metode_bayar' => 'dp',
                        'cara_bayar' => $data['cara_bayar'],
                        'no_referensi' => $data['no_referensi'] ?? null,
                        'jumlah_dibayar' => $jumlahDp,
                        'jumlah_piutang' => $total - $jumlahDp,
                    ]);
                })(),
                default => $order->update([
                    'status_bayar' => 'lunas',
                    'metode_bayar' => 'tunai',
                    'cara_bayar' => $data['cara_bayar'],
                    'no_referensi' => $data['no_referensi'] ?? null,
                    'jumlah_dibayar' => $total,
                    'jumlah_piutang' => 0,
                ]),
            };

            $order->update([
                'kasir_user_id' => auth()->id(),
                'dibayar_at' => now(),
                'status' => 'desain',
            ]);

            $catatan = trim(collect([
                $data['catatan'] ?? null,
                $data['metode_bayar'] !== 'hutang' ? $this->caraBayarLabel($data['cara_bayar'], $data['no_referensi'] ?? null) : null,
            ])->filter()->implode(' — '));

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => $catatan ?: null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            // Hutang doesn't move any cash — nothing to log in the payment
            // ledger until it's actually paid off.
            if ($data['metode_bayar'] !== 'hutang') {
                OrderPayment::create([
                    'order_type' => $type,
                    'order_id' => $order->id,
                    'jenis' => $data['metode_bayar'] === 'dp' ? 'dp' : 'lunas',
                    'jumlah' => $data['metode_bayar'] === 'dp' ? (float) $data['jumlah_dp'] : $total,
                    'cara_bayar' => $data['cara_bayar'],
                    'no_referensi' => $data['no_referensi'] ?? null,
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            // Revenue is recognized in full at the moment of sale — goods go
            // into production right away regardless of arrangement — so
            // hutang and DP both credit the full sale price, just split
            // across Kas/Piutang differently depending on what's actually
            // in hand yet.
            $kdBantu = $order->customer?->KdCust ?? '';

            match ($data['metode_bayar']) {
                'hutang' => $this->accounting->post(
                    now()->format('Y-m-d'), $order->NoOrder, 'Penjualan hutang '.$order->NoOrder,
                    [
                        ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'debet' => $total, 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                    ]
                ),
                'dp' => $this->accounting->post(
                    now()->format('Y-m-d'), $order->NoOrder, 'Penjualan DP '.$order->NoOrder,
                    [
                        ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'debet' => (float) $data['jumlah_dp'], 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'debet' => $total - (float) $data['jumlah_dp'], 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                    ]
                ),
                default => $this->accounting->post(
                    now()->format('Y-m-d'), $order->NoOrder, 'Penjualan lunas '.$order->NoOrder,
                    [
                        ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'debet' => $total, 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                    ]
                ),
            };
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
    public function lunasi(Request $request, string $type, int $id): RedirectResponse
    {
        if ($type !== 'outdoor') {
            abort(404);
        }

        $order = OrderOutdoor::findOrFail($id);

        if ($order->status_bayar !== 'dp' || (float) $order->jumlah_piutang <= 0) {
            return back()->with('error', 'Order ini tidak sedang menunggu pelunasan DP.');
        }

        $data = $request->validate([
            'cara_bayar' => ['required', 'in:tunai,qris,transfer'],
            'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($order, $type, $data) {
            $sisaPiutang = (float) $order->jumlah_piutang;

            $order->update([
                'status_bayar' => 'lunas',
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'jumlah_dibayar' => $order->total,
                'jumlah_piutang' => 0,
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => 'Pelunasan sisa DP — '.$this->caraBayarLabel($data['cara_bayar'], $data['no_referensi'] ?? null),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            OrderPayment::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'jenis' => 'pelunasan_dp',
                'jumlah' => $sisaPiutang,
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->accounting->post(
                now()->format('Y-m-d'), $order->NoOrder, 'Pelunasan DP '.$order->NoOrder,
                [
                    ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'debet' => $sisaPiutang],
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $sisaPiutang, 'kd_bantu' => $order->customer?->KdCust ?? ''],
                ]
            );
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Sisa DP berhasil dilunasi.')
            ->with('autoPrintInvoice', true);
    }

    /**
     * Settle a hutang (VIP credit) order — unlike DP this applies to all 3
     * order types, since hutang isn't Outdoor-only. Paying it off also frees
     * up the customer's credit limit for future hutang.
     */
    public function lunasiHutang(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer.limit');

        if ($order->status_bayar !== 'hutang' || (float) $order->jumlah_piutang <= 0) {
            return back()->with('error', 'Order ini tidak sedang menunggu pelunasan hutang.');
        }

        $data = $request->validate([
            'cara_bayar' => ['required', 'in:tunai,qris,transfer'],
            'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($order, $type, $data) {
            $sisaPiutang = (float) $order->jumlah_piutang;

            $order->update([
                'status_bayar' => 'lunas',
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'jumlah_dibayar' => $order->total,
                'jumlah_piutang' => 0,
            ]);

            if ($order->customer?->limit) {
                $order->customer->limit->decrement('Total', $sisaPiutang);
            }

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => 'Pelunasan hutang — '.$this->caraBayarLabel($data['cara_bayar'], $data['no_referensi'] ?? null),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            OrderPayment::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'jenis' => 'pelunasan_hutang',
                'jumlah' => $sisaPiutang,
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->accounting->post(
                now()->format('Y-m-d'), $order->NoOrder, 'Pelunasan hutang '.$order->NoOrder,
                [
                    ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'debet' => $sisaPiutang],
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $sisaPiutang, 'kd_bantu' => $order->customer?->KdCust ?? ''],
                ]
            );
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Hutang berhasil dilunasi.')
            ->with('autoPrintInvoice', true);
    }

    /**
     * A kasir asks for either a percentage or a flat Rupiah amount off the
     * whole nota. Doesn't touch money by itself — bayar() only applies it
     * once diskonStatus() is 'approved'.
     */
    public function requestDiskon(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        if ($order->status_bayar === 'lunas') {
            return back()->with('error', 'Order ini sudah lunas, tidak bisa diajukan diskon.');
        }

        if ($order->diskonStatus() === 'pending') {
            return back()->with('error', 'Order ini sudah punya pengajuan diskon yang masih menunggu persetujuan.');
        }

        $data = $request->validate([
            'diskon_tipe' => ['required', 'in:persen,nominal'],
            'diskon_persen' => ['required_if:diskon_tipe,persen', 'nullable', 'numeric', 'min:0.01', 'max:100'],
            'diskon_nominal' => ['required_if:diskon_tipe,nominal', 'nullable', 'numeric', 'min:1', 'max:'.(float) $order->total],
            'diskon_alasan' => ['required', 'string', 'max:255'],
        ]);

        $order->update([
            'diskon_tipe' => $data['diskon_tipe'],
            'diskon_requested_persen' => $data['diskon_tipe'] === 'persen' ? $data['diskon_persen'] : null,
            'diskon_requested_nominal' => $data['diskon_tipe'] === 'nominal' ? $data['diskon_nominal'] : null,
            'diskon_alasan' => $data['diskon_alasan'],
            'diskon_requested_at' => now(),
            'diskon_requested_by' => auth()->id(),
            'diskon_approved_at' => null,
            'diskon_approved_by' => null,
            'diskon_rejected_at' => null,
            'diskon_rejected_by' => null,
        ]);

        $label = $data['diskon_tipe'] === 'persen'
            ? "{$data['diskon_persen']}%"
            : 'Rp '.number_format((float) $data['diskon_nominal'], 0, ',', '.');

        $this->notifyDiskonApprovers($order, $label, $data['diskon_alasan']);

        return back()->with('status', 'Pengajuan diskon terkirim, menunggu persetujuan Admin/Owner/Admin Kasir.');
    }

    public function approveDiskon(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        if ($order->diskonStatus() !== 'pending') {
            return back()->with('error', 'Tidak ada pengajuan diskon yang menunggu persetujuan untuk order ini.');
        }

        $order->update([
            'diskon_persen' => $order->diskon_tipe === 'persen' ? $order->diskon_requested_persen : null,
            'diskon_nominal_tetap' => $order->diskon_tipe === 'nominal' ? $order->diskon_requested_nominal : null,
            'diskon_approved_at' => now(),
            'diskon_approved_by' => auth()->id(),
        ]);

        return back()->with('status', "Diskon {$order->diskonRequestedLabel()} untuk order {$order->NoOrder} disetujui.");
    }

    public function rejectDiskon(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        if ($order->diskonStatus() !== 'pending') {
            return back()->with('error', 'Tidak ada pengajuan diskon yang menunggu persetujuan untuk order ini.');
        }

        $order->update([
            'diskon_rejected_at' => now(),
            'diskon_rejected_by' => auth()->id(),
        ]);

        return back()->with('status', "Pengajuan diskon untuk order {$order->NoOrder} ditolak.");
    }

    /**
     * Pings every Admin/Owner/Admin Kasir via the existing 1:1 chat system —
     * there's no broadcast/group channel, so this just fires one direct
     * message per approver, from the kasir who made the request.
     */
    private function notifyDiskonApprovers(Model $order, string $label, string $alasan): void
    {
        $approverRoleIds = Role::query()
            ->whereJsonContains('permissions', 'kasir.approve-diskon')
            ->pluck('id');

        $approvers = User::query()
            ->whereIn('role_id', $approverRoleIds)
            ->where('id', '!=', auth()->id())
            ->get();

        $body = "Pengajuan diskon {$label} untuk order {$order->NoOrder}"
            .($order->customer?->NmCust ? ' ('.ucwords(mb_strtolower($order->customer->NmCust)).')' : '')
            ." — alasan: {$alasan}. Menunggu persetujuan.";

        foreach ($approvers as $approver) {
            ChatMessage::create([
                'sender_id' => auth()->id(),
                'recipient_id' => $approver->id,
                'body' => $body,
            ]);
        }
    }

    private function caraBayarLabel(?string $caraBayar, ?string $noReferensi): string
    {
        $label = match ($caraBayar) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Tunai',
        };

        return $noReferensi ? "{$label} (Ref: {$noReferensi})" : $label;
    }
}
