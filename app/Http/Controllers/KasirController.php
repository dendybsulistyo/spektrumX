<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CustomerCreditService;
use App\Services\OrderPricingService;
use App\Support\Rupiah;
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
        private readonly OrderPricingService $pricingService,
    ) {}

    public function index(Request $request): View
    {
        $initialTab = in_array($request->query('tab'), ['indoor', 'outdoor', 'replacement', 'dp', 'lunas'], true)
            ? $request->query('tab')
            : 'indoor';

        $indoorOrders = OrderIndoor::query()
            ->with('customer.limit')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        $outdoorOrders = OrderOutdoor::query()
            ->with('customer.limit')
            ->where('status_bayar', 'belum_bayar')
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->get();

        // Outdoor orders paid via DP still owe a balance — surfaced here so
        // kasir can record the pelunasan (settlement) whenever it comes in.
        $dpOrders = OrderOutdoor::query()
            ->with('customer.limit')
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

        // Orders already paid but still somewhere in production — a kasir
        // needs to be able to cancel these too (customer changed their mind
        // after paying), and doing it from here keeps the cancellation tied
        // to the same place the payment itself was recorded, so the
        // financial reports stay consistent.
        $lunasOrders = collect();
        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $orderType => $model) {
            $model::query()
                ->with('customer')
                ->where('status_bayar', 'lunas')
                ->whereNotIn('status', ['selesai', 'batal'])
                ->orderByDesc('dibayar_at')
                ->get()
                ->each(function ($order) use (&$lunasOrders, $orderType) {
                    $order->order_type = $orderType;
                    $lunasOrders->push($order);
                });
        }
        $lunasOrders = $lunasOrders->sortByDesc('dibayar_at')->values();

        // Needed to hide "Batalkan Order" on rows that already have any
        // pending rework request (ulang or batal) — OrderReworkController::
        // store() rejects a second one for the same order regardless of kind.
        $pendingRework = OrderReworkRequest::pendingMap();

        return view('kasir.index', compact('indoorOrders', 'outdoorOrders', 'dpOrders', 'replacementOrders', 'lunasOrders', 'pendingRework', 'initialTab'));
    }

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load(['customer.limit', 'replaces']);

        $rawItems = $type === 'indoor' ? $order->detailItems() : $order->items;
        $items = $this->pricingService->detailedLineItems($type, $order, $rawItems);

        $pendingRework = OrderReworkRequest::forOrder($type, $id)->pending()->exists();

        return view('kasir.show', ['type' => $type, 'order' => $order, 'items' => $items, 'pendingRework' => $pendingRework]);
    }

    public function bayar(Request $request, string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);

        // Nota pengganti only ever moves the topup/cashback difference in
        // one go, via the untouched single cara_bayar path below — splitting
        // across methods is only offered for a normal lunas/DP collection.
        $isReplacement = (bool) $order->replacement_order_id;

        $rules = [
            'metode_bayar' => ['required', 'in:tunai,hutang,dp'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];

        $rules += $isReplacement
            ? [
                'cara_bayar' => ['required_if:metode_bayar,tunai,dp', 'nullable', 'in:tunai,qris,transfer'],
                'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
                'jumlah_bayar' => ['nullable', 'numeric', 'min:0', 'multiple_of:100'],
            ]
            : [
                // Rincian belongs only to cash/DP payments. The UI hides it
                // for hutang, but clients can still submit stale fields, so
                // exclude it here instead of validating an irrelevant value.
                'rincian' => ['exclude_unless:metode_bayar,tunai,dp', 'required', 'array'],
                'rincian.*.cara_bayar' => ['exclude_unless:metode_bayar,tunai,dp', 'required_with:rincian', 'in:tunai,qris,transfer'],
                'rincian.*.jumlah' => ['exclude_unless:metode_bayar,tunai,dp', 'required_with:rincian', 'numeric', 'min:100', 'multiple_of:100'],
                'rincian.*.no_referensi' => ['exclude_unless:metode_bayar,tunai,dp', 'nullable', 'string', 'max:50'],
            ];

        $data = $request->validate($rules);

        $order->load('customer.limit');

        if ($order->diskonStatus() === 'pending') {
            return back()->with('error', 'Order ini sedang menunggu persetujuan diskon — tidak bisa diproses dulu.');
        }

        $total = Rupiah::bulatkan($order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total);

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

            // POS-style: if there's a topup owed, the kasir can enter what
            // the customer actually handed over — only the amount actually
            // owed gets recorded as revenue, any excess is change.
            $kembalian = 0.0;
            if ($topup > 0) {
                $jumlahBayar = (float) ($data['jumlah_bayar'] ?? 0);

                if ($jumlahBayar + 0.5 < $topup) {
                    return back()->with('error', 'Jumlah bayar (Rp '.number_format($jumlahBayar, 0, ',', '.').') kurang dari tambahan yang harus dibayar Rp '.number_format($topup, 0, ',', '.').'.')->withInput();
                }

                $kembalian = $jumlahBayar - $topup;
            }

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
                ->with('status', ($cashback > 0 ? 'Nota pengganti selesai. Cashback telah dicatat.' : 'Nota pengganti selesai. Tambahan pembayaran telah dicatat.').($kembalian > 0 ? ' Kembalian: Rp '.number_format($kembalian, 0, ',', '.').'.' : ''))
                ->with('autoPrintInvoice', true);
        }

        if ($order->hutangApprovalStatus() === 'pending') {
            return back()->with('error', 'Order ini sedang menunggu persetujuan hutang — tidak bisa diproses dulu.');
        }

        // Hutang is handled entirely on its own — no rincian split, no
        // shared DB::transaction with tunai/DP below, since a VIP over
        // plafon doesn't commit anything yet (see commitHutang()).
        if ($data['metode_bayar'] === 'hutang') {
            if (! $order->customer?->isVip) {
                return back()->with('error', 'Customer bukan VIP, tidak bisa hutang.');
            }

            if ($order->withinHutangPlafon()) {
                $this->commitHutang($order, $type, $total, $data['catatan'] ?? null, auth()->id());

                return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
                    ->with('status', 'Pembayaran berhasil diproses.')
                    ->with('autoPrintInvoice', true);
            }

            // Over plafon — held for Admin/Admin Kasir sign-off instead of
            // an outright rejection. See approveHutang()/rejectHutang().
            $order->update([
                'hutang_catatan' => $data['catatan'] ?? null,
                'hutang_requested_at' => now(),
                'hutang_requested_by' => auth()->id(),
                'hutang_approved_at' => null,
                'hutang_approved_by' => null,
                'hutang_rejected_at' => null,
                'hutang_rejected_by' => null,
            ]);

            $this->notifyHutangApprovers($order, $total);

            return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
                ->with('status', 'Pembayaran melebihi plafon hutang — pengajuan terkirim, menunggu persetujuan Admin/Admin Kasir.');
        }

        // The DP amount used to be its own "Jumlah DP" input, entered
        // separately from — and required to match — the rincian
        // split-payment total below. That was the same number typed twice;
        // rincian's total is now the only source of truth for how much DP
        // was paid.
        $jumlahDp = 0.0;

        if ($data['metode_bayar'] === 'dp') {
            // DP is an Outdoor-only facility — the goods still get produced
            // and handed over, but the balance must be settled (via lunasi())
            // before Pengambilan will release them to the customer.
            if ($type !== 'outdoor') {
                return back()->with('error', 'DP hanya berlaku untuk order outdoor.');
            }

            $jumlahDp = (float) collect($data['rincian'] ?? [])->sum('jumlah');
            $minimumDp = Rupiah::bulatkanKeAtas($total * 0.5);

            if ($jumlahDp < $minimumDp) {
                return back()->with('error', 'DP minimal 50% dari total order (Rp '.number_format($minimumDp, 0, ',', '.').').');
            }

            if ($jumlahDp >= $total) {
                return back()->with('error', 'Jumlah DP tidak boleh sama dengan atau melebihi total order — gunakan pembayaran tunai (lunas) sebagai gantinya.');
            }
        }

        $rincian = $data['rincian'] ?? [];
        $target = $data['metode_bayar'] === 'dp' ? $jumlahDp : $total;

        if ($rincianError = $this->checkRincian($rincian, $target)) {
            return back()->with('error', $rincianError)->withInput();
        }

        [$rincian, $kembalian] = $this->capRincianToTarget($rincian, $target);

        DB::transaction(function () use ($order, $type, $data, $total, $rincian, $jumlahDp) {
            $caraBayar = $this->dominantCaraBayar($rincian);
            $noReferensi = $this->dominantNoReferensi($rincian);

            match ($data['metode_bayar']) {
                'dp' => $order->update([
                    'status_bayar' => 'dp',
                    'metode_bayar' => 'dp',
                    'cara_bayar' => $caraBayar,
                    'no_referensi' => $noReferensi,
                    'jumlah_dibayar' => $jumlahDp,
                    'jumlah_piutang' => $total - $jumlahDp,
                ]),
                default => $order->update([
                    'status_bayar' => 'lunas',
                    'metode_bayar' => 'tunai',
                    'cara_bayar' => $caraBayar,
                    'no_referensi' => $noReferensi,
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
                $this->caraBayarLabel($caraBayar, $noReferensi),
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

            $this->createPaymentRows($type, $order->id, $data['metode_bayar'] === 'dp' ? 'dp' : 'lunas', $rincian);

            $kdBantu = $order->customer?->KdCust ?? '';

            match ($data['metode_bayar']) {
                'dp' => $this->accounting->post(
                    now()->format('Y-m-d'), $order->NoOrder, 'Penjualan DP '.$order->NoOrder,
                    [
                        ...$this->kasLines($rincian, $kdBantu),
                        ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'debet' => $total - $jumlahDp, 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                    ]
                ),
                default => $this->accounting->post(
                    now()->format('Y-m-d'), $order->NoOrder, 'Penjualan lunas '.$order->NoOrder,
                    [
                        ...$this->kasLines($rincian, $kdBantu),
                        ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                    ]
                ),
            };
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Pembayaran berhasil diproses.'.($kembalian > 0 ? ' Kembalian: Rp '.number_format($kembalian, 0, ',', '.').'.' : ''))
            ->with('autoPrintInvoice', true);
    }

    /**
     * Actually commits an order as hutang — addHutang() plus the note/
     * accounting trail. Shared by bayar() (VIP still within plafon, commits
     * immediately) and approveHutang() (VIP was over plafon, commits once
     * Admin/Admin Kasir signs off) so the money-moving code only lives once.
     */
    private function commitHutang(Model $order, string $type, float $total, ?string $catatan, int $kasirUserId, ?int $approvedBy = null): void
    {
        DB::transaction(function () use ($order, $type, $total, $catatan, $kasirUserId, $approvedBy) {
            $this->creditService->addHutang($order->customer, $total);

            $order->update([
                'status_bayar' => 'hutang',
                'metode_bayar' => 'hutang',
                'cara_bayar' => null,
                'no_referensi' => null,
                'jumlah_dibayar' => 0,
                'jumlah_piutang' => $total,
                'kasir_user_id' => $kasirUserId,
                'dibayar_at' => now(),
                'status' => 'desain',
                'hutang_approved_at' => $approvedBy ? now() : null,
                'hutang_approved_by' => $approvedBy,
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => $catatan ?: null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->accounting->post(
                now()->format('Y-m-d'), $order->NoOrder, 'Penjualan hutang '.$order->NoOrder,
                [
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'debet' => $total, 'kd_bantu' => $order->customer?->KdCust ?? ''],
                    ['akun' => AccountingService::AKUN_PENJUALAN, 'kredit' => $total],
                ]
            );
        });
    }

    /**
     * Admin/Admin Kasir sign-off for a hutang that would push a VIP customer
     * past their plafon — gated at the route level by kasir.approve-hutang.
     */
    public function approveHutang(string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);
        abort_if($order->hutangApprovalStatus() !== 'pending', 422, 'Tidak ada pengajuan hutang yang menunggu persetujuan untuk order ini.');

        $order->load('customer.limit');

        $this->commitHutang($order, $type, $order->hutangAmount(), $order->hutang_catatan, (int) $order->hutang_requested_by, auth()->id());

        return back()->with('status', "Pengajuan hutang order {$order->NoOrder} disetujui.");
    }

    public function rejectHutang(string $type, int $id): RedirectResponse
    {
        $order = $this->resolveOrder($type, $id);
        abort_if($order->hutangApprovalStatus() !== 'pending', 422, 'Tidak ada pengajuan hutang yang menunggu persetujuan untuk order ini.');

        $order->update([
            'hutang_rejected_at' => now(),
            'hutang_rejected_by' => auth()->id(),
        ]);

        return back()->with('status', "Pengajuan hutang order {$order->NoOrder} ditolak.");
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
            'rincian' => ['required', 'array'],
            'rincian.*.cara_bayar' => ['required_with:rincian', 'in:tunai,qris,transfer'],
            'rincian.*.jumlah' => ['required_with:rincian', 'numeric', 'min:100', 'multiple_of:100'],
            'rincian.*.no_referensi' => ['nullable', 'string', 'max:50'],
        ]);

        $sisaPiutang = (float) $order->jumlah_piutang;

        if ($rincianError = $this->checkRincian($data['rincian'], $sisaPiutang)) {
            return back()->with('error', $rincianError)->withInput();
        }

        [$rincian, $kembalian] = $this->capRincianToTarget($data['rincian'], $sisaPiutang);

        DB::transaction(function () use ($order, $type, $sisaPiutang, $rincian) {
            $caraBayar = $this->dominantCaraBayar($rincian);
            $noReferensi = $this->dominantNoReferensi($rincian);

            $order->update([
                'status_bayar' => 'lunas',
                'cara_bayar' => $caraBayar,
                'no_referensi' => $noReferensi,
                'jumlah_dibayar' => $order->total,
                'jumlah_piutang' => 0,
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'kasir',
                'action' => 'selesai',
                'catatan' => 'Pelunasan sisa DP — '.$this->caraBayarLabel($caraBayar, $noReferensi),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->createPaymentRows($type, $order->id, 'pelunasan_dp', $rincian);

            $this->accounting->post(
                now()->format('Y-m-d'), $order->NoOrder, 'Pelunasan DP '.$order->NoOrder,
                [
                    ...$this->kasLines($rincian, $order->customer?->KdCust ?? ''),
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $sisaPiutang, 'kd_bantu' => $order->customer?->KdCust ?? ''],
                ]
            );
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Sisa DP berhasil dilunasi.'.($kembalian > 0 ? ' Kembalian: Rp '.number_format($kembalian, 0, ',', '.').'.' : ''))
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
            'rincian' => ['required', 'array'],
            'rincian.*.cara_bayar' => ['required_with:rincian', 'in:tunai,qris,transfer'],
            'rincian.*.jumlah' => ['required_with:rincian', 'numeric', 'min:100', 'multiple_of:100'],
            'rincian.*.no_referensi' => ['nullable', 'string', 'max:50'],
        ]);

        $sisaPiutang = (float) $order->jumlah_piutang;

        if ($rincianError = $this->checkRincian($data['rincian'], $sisaPiutang)) {
            return back()->with('error', $rincianError)->withInput();
        }

        [$rincian, $kembalian] = $this->capRincianToTarget($data['rincian'], $sisaPiutang);

        DB::transaction(function () use ($order, $type, $sisaPiutang, $rincian) {
            $caraBayar = $this->dominantCaraBayar($rincian);
            $noReferensi = $this->dominantNoReferensi($rincian);

            $order->update([
                'status_bayar' => 'lunas',
                'cara_bayar' => $caraBayar,
                'no_referensi' => $noReferensi,
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
                'catatan' => 'Pelunasan hutang — '.$this->caraBayarLabel($caraBayar, $noReferensi),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->createPaymentRows($type, $order->id, 'pelunasan_hutang', $rincian);

            $this->accounting->post(
                now()->format('Y-m-d'), $order->NoOrder, 'Pelunasan hutang '.$order->NoOrder,
                [
                    ...$this->kasLines($rincian, $order->customer?->KdCust ?? ''),
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $sisaPiutang, 'kd_bantu' => $order->customer?->KdCust ?? ''],
                ]
            );
        });

        return redirect()->route('kasir.show', ['type' => $type, 'id' => $order->id])
            ->with('status', 'Hutang berhasil dilunasi.'.($kembalian > 0 ? ' Kembalian: Rp '.number_format($kembalian, 0, ',', '.').'.' : ''))
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
            'diskon_nominal' => ['required_if:diskon_tipe,nominal', 'nullable', 'numeric', 'min:100', 'multiple_of:100', 'max:'.(float) $order->total],
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

    /**
     * Same pattern as notifyDiskonApprovers() — pings every Admin/Admin
     * Kasir via the 1:1 chat system when a hutang would push a VIP customer
     * past their plafon.
     */
    private function notifyHutangApprovers(Model $order, float $total): void
    {
        $approverRoleIds = Role::query()
            ->whereJsonContains('permissions', 'kasir.approve-hutang')
            ->pluck('id');

        $approvers = User::query()
            ->whereIn('role_id', $approverRoleIds)
            ->where('id', '!=', auth()->id())
            ->get();

        $sisa = (float) $order->customer->limit->Batas - (float) $order->customer->limit->Total;

        $body = "Pengajuan hutang melebihi plafon untuk order {$order->NoOrder}"
            .($order->customer?->NmCust ? ' ('.ucwords(mb_strtolower($order->customer->NmCust)).')' : '')
            .' — total Rp '.number_format($total, 0, ',', '.')
            .', sisa plafon Rp '.number_format($sisa, 0, ',', '.').'. Menunggu persetujuan.';

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
            'campuran' => 'Campuran',
            default => 'Tunai',
        };

        return $noReferensi ? "{$label} (Ref: {$noReferensi})" : $label;
    }

    /**
     * Cross-field checks a split-payment array (rincian) needs that plain
     * Laravel validation rules can't express on their own: every non-tunai
     * split needs a reference number, and the rows must cover at least the
     * amount actually being collected right now. Paying MORE than that is
     * fine — POS-style — capRincianToTarget() below turns the excess into
     * change instead of extra revenue.
     *
     * @param  array<int, array{cara_bayar: string, jumlah: mixed, no_referensi?: ?string}>  $rincian
     */
    private function checkRincian(array $rincian, float $target): ?string
    {
        if (empty($rincian)) {
            return 'Rincian pembayaran wajib diisi.';
        }

        foreach ($rincian as $row) {
            if (($row['cara_bayar'] ?? null) !== 'tunai' && empty($row['no_referensi'])) {
                return 'No. Referensi wajib diisi untuk pembayaran QRIS/Transfer.';
            }
        }

        $sum = (float) array_sum(array_column($rincian, 'jumlah'));

        if ($sum + 0.5 < $target) {
            return 'Total rincian pembayaran (Rp '.number_format($sum, 0, ',', '.').') kurang dari Rp '.number_format($target, 0, ',', '.').'.';
        }

        return null;
    }

    /**
     * Caps a POS-style overpayment down to the amount actually owed — the
     * excess is change handed back in cash, not revenue, so it must never
     * land in OrderPayment or the accounting ledger. Trimmed from the tunai
     * portion first (that's where physical change actually comes from); if
     * there's no tunai row at all, trims from the last row as a fallback.
     *
     * @param  array<int, array{cara_bayar: string, jumlah: mixed, no_referensi?: ?string}>  $rincian
     * @return array{0: array<int, array{cara_bayar: string, jumlah: mixed, no_referensi?: ?string}>, 1: float}
     */
    private function capRincianToTarget(array $rincian, float $target): array
    {
        $sum = (float) array_sum(array_column($rincian, 'jumlah'));
        $kembalian = $sum - $target;

        if ($kembalian <= 0.5) {
            return [$rincian, 0.0];
        }

        $trimIndex = null;
        foreach ($rincian as $i => $row) {
            if (($row['cara_bayar'] ?? null) === 'tunai') {
                $trimIndex = $i;
                break;
            }
        }
        $trimIndex ??= array_key_last($rincian);

        $rincian[$trimIndex]['jumlah'] = max(0, (float) $rincian[$trimIndex]['jumlah'] - $kembalian);

        // Drop the row entirely if trimming zeroed it out, so an empty
        // OrderPayment row never gets created.
        if ($rincian[$trimIndex]['jumlah'] <= 0) {
            unset($rincian[$trimIndex]);
            $rincian = array_values($rincian);
        }

        return [$rincian, $kembalian];
    }

    /**
     * The order's own cara_bayar column can only hold one value —
     * 'campuran' is stored whenever the kasir actually used more than one
     * method, with the real per-method breakdown living in the individual
     * OrderPayment rows instead.
     */
    private function dominantCaraBayar(array $rincian): string
    {
        $methods = collect($rincian)->pluck('cara_bayar')->unique();

        return $methods->count() === 1 ? $methods->first() : 'campuran';
    }

    private function dominantNoReferensi(array $rincian): ?string
    {
        return count($rincian) === 1 ? ($rincian[0]['no_referensi'] ?? null) : null;
    }

    /**
     * One OrderPayment row per split, so a DP paid as 25rb QRIS + 25rb
     * transfer shows up as two distinct ledger entries instead of one row
     * that can only carry a single cara_bayar.
     */
    private function createPaymentRows(string $type, int $orderId, string $jenis, array $rincian): void
    {
        foreach ($rincian as $row) {
            OrderPayment::create([
                'order_type' => $type,
                'order_id' => $orderId,
                'jenis' => $jenis,
                'jumlah' => (float) $row['jumlah'],
                'cara_bayar' => $row['cara_bayar'],
                'no_referensi' => $row['no_referensi'] ?? null,
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Groups a split by which kas account it actually lands in — QRIS and
     * transfer both post to the same bank account (see
     * AccountingService::akunKasFor()), so a QRIS+transfer split still
     * yields just one debit line, not two.
     *
     * @return array<int, array{akun: string, debet: float, kd_bantu: string}>
     */
    private function kasLines(array $rincian, string $kdBantu): array
    {
        return collect($rincian)
            ->groupBy(fn ($row) => AccountingService::akunKasFor($row['cara_bayar']))
            ->map(fn ($rows) => (float) array_sum(array_column($rows->all(), 'jumlah')))
            ->map(fn ($jumlah, $akun) => ['akun' => $akun, 'debet' => $jumlah, 'kd_bantu' => $kdBantu])
            ->values()
            ->all();
    }
}
