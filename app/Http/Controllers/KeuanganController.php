<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Customer;
use App\Models\JurnalEntry;
use App\Models\LaporanPpnFinal;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\PengaturanKeuangan;
use App\Models\User;
use App\Services\OrderDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
    /**
     * Daily cash reconciliation — every cash-in event (lunas, DP, pelunasan
     * DP, nota pengganti) for the selected date, broken down by payment
     * channel so the cashier can match this against the physical cash
     * drawer / QRIS & transfer mutations at closing.
     */
    public function kasHarian(Request $request): View
    {
        $tanggal = $request->filled('tanggal') ? $request->string('tanggal')->toString() : now()->format('Y-m-d');

        $payments = OrderPayment::with('user')
            ->whereDate('created_at', $tanggal)
            ->orderBy('created_at')
            ->get();

        // Resolve NoOrder/customer per order without N+1 — group ids by
        // type, one query per type, then map back onto each payment row.
        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();

        foreach ($payments->groupBy('order_type') as $type => $rows) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }

            $model::query()->with('customer')->whereIn('id', $rows->pluck('order_id')->unique())->get()
                ->each(function ($order) use (&$ordersByKey, $type) {
                    $ordersByKey["{$type}-{$order->id}"] = $order;
                });
        }

        $rows = $payments->map(function (OrderPayment $p) use ($ordersByKey) {
            $order = $ordersByKey["{$p->order_type}-{$p->order_id}"] ?? null;
            $jumlah = (float) $p->jumlah;

            return [
                'waktu' => $p->created_at,
                'no_order' => $order?->NoOrder ?? '-',
                'tipe' => ucfirst($p->order_type),
                'customer' => $order?->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-',
                'jenis' => OrderPayment::JENIS_LABELS[$p->jenis] ?? $p->jenis,
                'cara_bayar' => $p->cara_bayar,
                'cara_bayar_label' => OrderPayment::CARA_BAYAR_LABELS[$p->cara_bayar] ?? $p->cara_bayar,
                'no_referensi' => $p->no_referensi,
                // Refund rows carry a negative jumlah — split into debit
                // (kas masuk) / kredit (kas keluar) so the cashier reads
                // money in vs. money out directly, no sign to interpret.
                'debit' => $jumlah > 0 ? $jumlah : null,
                'kredit' => $jumlah < 0 ? abs($jumlah) : null,
                'kasir' => $p->user?->name ?? '-',
            ];
        });

        $summaryFor = fn (string $caraBayar) => [
            'masuk' => (float) $payments->where('cara_bayar', $caraBayar)->where('jumlah', '>', 0)->sum('jumlah'),
            'keluar' => (float) $payments->where('cara_bayar', $caraBayar)->where('jumlah', '<', 0)->sum('jumlah') * -1,
        ];

        $summary = [
            'tunai' => $summaryFor('tunai'),
            'qris' => $summaryFor('qris'),
            'transfer' => $summaryFor('transfer'),
        ];

        $totalMasuk = (float) $payments->where('jumlah', '>', 0)->sum('jumlah');
        $totalKeluar = (float) $payments->where('jumlah', '<', 0)->sum('jumlah') * -1;

        return view('keuangan.kas-harian', [
            'tanggal' => $tanggal,
            'rows' => $rows,
            'summary' => $summary,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'totalNet' => $totalMasuk - $totalKeluar,
            'jumlahTransaksi' => $payments->count(),
        ]);
    }

    /**
     * Total cash brought in by each cashier operator over a date range —
     * "kasir pagi hasilnya berapa, kasir sore hasilnya berapa" — grouped by
     * whoever was logged in when the OrderPayment row was created. Refunds
     * (negative jumlah) net out against that same operator's total, same
     * debit/kredit split as kasHarian().
     */
    public function rekapKasir(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $payments = OrderPayment::with('user')
            ->whereBetween('created_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->orderBy('created_at')
            ->get();

        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();
        foreach ($payments->groupBy('order_type') as $type => $typePayments) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }
            $model::query()->with('customer')->whereIn('id', $typePayments->pluck('order_id')->unique())->get()
                ->each(fn ($order) => $ordersByKey->put("{$type}-{$order->id}", $order));
        }

        $rows = $payments->groupBy(fn (OrderPayment $p) => $p->user_id)
            ->map(function ($group) {
                $masuk = (float) $group->where('jumlah', '>', 0)->sum('jumlah');
                $keluar = (float) $group->where('jumlah', '<', 0)->sum('jumlah') * -1;

                return [
                    'user_id' => $group->first()->user_id,
                    'kasir' => $group->first()->user?->name ?? '-',
                    'jumlah_transaksi' => $group->count(),
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'net' => $masuk - $keluar,
                ];
            })
            ->sortByDesc('net')
            ->values();

        $groups = $payments->groupBy(fn (OrderPayment $payment) => $payment->user_id ?: 0)
            ->map(function (Collection $userPayments) use ($ordersByKey) {
                $detail = collect();
                foreach ($userPayments as $payment) {
                    $order = $ordersByKey["{$payment->order_type}-{$payment->order_id}"] ?? null;
                    $customer = $order?->customer?->NmCust ?: '-';
                    $amount = (float) $payment->jumlah;
                    $kind = OrderPayment::JENIS_LABELS[$payment->jenis] ?? ucfirst($payment->jenis);
                    $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
                    $section = match ($payment->jenis) {
                        'dp' => 'Uang Muka',
                        'pelunasan_hutang' => 'Penerimaan Piutang',
                        default => 'Penerimaan Nota Tunai',
                    };

                    if ($amount >= 0) {
                        $detail->push([
                            'section' => $section,
                            'number' => $order?->NoOrder ?? '-',
                            'description' => "{$kind} - {$customer}",
                            'debit' => $amount,
                            'credit' => 0.0,
                        ]);
                        if ($payment->cara_bayar !== 'tunai') {
                            $detail->push([
                                'section' => 'Penerimaan Tidak Tunai',
                                'number' => '',
                                'description' => "Bayar via {$method} - {$customer}",
                                'debit' => 0.0,
                                'credit' => $amount,
                            ]);
                        }
                    } else {
                        $detail->push([
                            'section' => 'Refund / Pengeluaran Kas',
                            'number' => $order?->NoOrder ?? '-',
                            'description' => "Refund via {$method} - {$customer}",
                            'debit' => 0.0,
                            'credit' => abs($amount),
                        ]);
                    }
                }

                $sectionOrder = ['Saldo Awal', 'Penerimaan Nota Tunai', 'Penerimaan Piutang', 'Uang Muka', 'Penerimaan Tidak Tunai', 'Refund / Pengeluaran Kas'];
                $sections = collect($sectionOrder)->map(function (string $name) use ($detail) {
                    $sectionRows = $name === 'Saldo Awal'
                        ? collect([['section' => $name, 'number' => '', 'description' => 'Saldo Awal', 'debit' => 0.0, 'credit' => 0.0]])
                        : $detail->where('section', $name)->values();

                    return [
                        'name' => $name,
                        'rows' => $sectionRows,
                        'debit' => (float) $sectionRows->sum('debit'),
                        'credit' => (float) $sectionRows->sum('credit'),
                    ];
                })->filter(fn (array $section) => $section['name'] === 'Saldo Awal' || $section['rows']->isNotEmpty())->values();

                return [
                    'user_id' => $userPayments->first()->user_id,
                    'kasir' => $userPayments->first()->user?->name ?? '-',
                    'details' => $detail,
                    'sections' => $sections,
                    'debit' => (float) $detail->sum('debit'),
                    'credit' => (float) $detail->sum('credit'),
                ];
            })->sortBy('kasir')->values();

        return view('keuangan.rekap-kasir', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rows' => $rows,
            'totalMasuk' => (float) $payments->where('jumlah', '>', 0)->sum('jumlah'),
            'totalKeluar' => (float) $payments->where('jumlah', '<', 0)->sum('jumlah') * -1,
            'jumlahTransaksi' => $payments->count(),
            'groups' => $groups,
        ]);
    }

    /**
     * Drill-down from rekapKasir(): every customer a given kasir operator
     * personally processed payment for in the period, split by order type —
     * "kasir A layani customer siapa aja, indoor berapa outdoor berapa" —
     * plus how much of what they took in came in tunai/QRIS/transfer.
     *
     * Order counts are keyed off each order's own kasir_user_id/dibayar_at
     * (who actually pressed "Proses Pembayaran"), so a DP + separate
     * pelunasan on the same order still counts as one order, not two. The
     * money breakdown, however, is sourced from the OrderPayment ledger
     * instead — a single payment can now be split across methods (e.g. DP
     * paid as half QRIS half transfer), which the order's own cara_bayar
     * column can no longer represent (it just says 'campuran'), so the
     * ledger is the only accurate source for "how much came in via which
     * method". Hutang orders contribute to the indoor/outdoor counts but
     * nothing to the cara_bayar totals, since no cash has actually moved.
     */
    public function rekapKasirCustomer(Request $request, int $kasir): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');

        $kasirUser = User::findOrFail($kasir);

        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class];

        $customers = collect();

        foreach ($models as $type => $model) {
            $model::query()
                ->with('customer')
                ->where('kasir_user_id', $kasir)
                ->whereBetween('dibayar_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
                ->get()
                ->each(function ($order) use (&$customers, $type) {
                    $key = $order->KdCust ?: '-';

                    if (! $customers->has($key)) {
                        $customers->put($key, [
                            'kode' => $order->KdCust,
                            'nama' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : ($order->KdCust ?: 'Tanpa customer'),
                            'indoor' => 0,
                            'outdoor' => 0,
                            'tunai' => 0.0,
                            'qris' => 0.0,
                            'transfer' => 0.0,
                            'no_order' => [],
                        ]);
                    }

                    $entry = $customers->get($key);
                    $entry[$type]++;
                    $entry['no_order'][] = $order->NoOrder;
                    $customers->put($key, $entry);
                });
        }

        $payments = OrderPayment::query()
            ->whereIn('order_type', array_keys($models))
            ->where('user_id', $kasir)
            ->whereBetween('created_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->get();

        // Resolve each payment's customer without N+1 — one query per order
        // type for the handful of orders referenced this period.
        $kdCustByOrder = collect();
        foreach ($payments->groupBy('order_type') as $type => $rows) {
            $models[$type]::query()
                ->whereIn('id', $rows->pluck('order_id')->unique())
                ->get(['id', 'KdCust'])
                ->each(function ($order) use (&$kdCustByOrder, $type) {
                    $kdCustByOrder["{$type}-{$order->id}"] = $order->KdCust;
                });
        }

        foreach ($payments as $payment) {
            if (! in_array($payment->cara_bayar, ['tunai', 'qris', 'transfer'], true)) {
                continue;
            }

            $kdCust = $kdCustByOrder["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $key = $kdCust ?: '-';

            if (! $customers->has($key)) {
                $customers->put($key, [
                    'kode' => $kdCust,
                    'nama' => $kdCust ?: 'Tanpa customer',
                    'indoor' => 0,
                    'outdoor' => 0,
                    'tunai' => 0.0,
                    'qris' => 0.0,
                    'transfer' => 0.0,
                    'no_order' => [],
                ]);
            }

            $entry = $customers->get($key);
            $entry[$payment->cara_bayar] += (float) $payment->jumlah;
            $customers->put($key, $entry);
        }

        $rows = $customers
            ->map(fn ($row) => $row + [
                'total' => $row['indoor'] + $row['outdoor'],
                'total_dibayar' => $row['tunai'] + $row['qris'] + $row['transfer'],
            ])
            ->sortByDesc('total_dibayar')
            ->values();

        return view('keuangan.rekap-kasir-customer', [
            'kasirUser' => $kasirUser,
            'dari' => $dari,
            'sampai' => $sampai,
            'rows' => $rows,
            'totalTunai' => $rows->sum('tunai'),
            'totalQris' => $rows->sum('qris'),
            'totalTransfer' => $rows->sum('transfer'),
        ]);
    }

    /**
     * Total order per customer — search-driven rather than listing every
     * customer, since with hundreds of customers a full list would just be
     * noise. Nothing is shown until a name/kode is searched (mirrors the
     * search pattern already used on CustomerController::index()).
     */
    public function rekapCustomer(Request $request): View
    {
        $search = $request->filled('search') ? $request->string('search')->toString() : null;

        $rows = collect();

        if ($search) {
            $customers = Customer::query()
                ->where('NmCust', 'like', "%{$search}%")
                ->orWhere('KdCust', 'like', "%{$search}%")
                ->orderBy('NmCust')
                ->limit(20)
                ->get();

            $models = ['Indoor' => OrderIndoor::class, 'Outdoor' => OrderOutdoor::class, 'Artwork' => OrderArtwork::class];

            $rows = $customers->map(function (Customer $customer) use ($models) {
                $jumlahOrder = 0;
                $totalNilai = 0.0;
                $totalDibayar = 0.0;
                $totalPiutang = 0.0;
                $perTipe = [];

                foreach ($models as $label => $model) {
                    $orders = $model::where('KdCust', $customer->KdCust)->get(['total', 'jumlah_dibayar', 'jumlah_piutang']);

                    if ($orders->isEmpty()) {
                        continue;
                    }

                    $jumlahOrder += $orders->count();
                    $totalNilai += (float) $orders->sum('total');
                    $totalDibayar += (float) $orders->sum('jumlah_dibayar');
                    $totalPiutang += (float) $orders->sum('jumlah_piutang');
                    $perTipe[] = "{$label} {$orders->count()}";
                }

                return [
                    'kode' => $customer->KdCust,
                    'nama' => $customer->NmCust,
                    'jumlah_order' => $jumlahOrder,
                    'per_tipe' => implode(' · ', $perTipe),
                    'total_nilai' => $totalNilai,
                    'total_dibayar' => $totalDibayar,
                    'total_piutang' => $totalPiutang,
                ];
            })->sortByDesc('total_nilai')->values();
        }

        return view('keuangan.rekap-customer', [
            'search' => $search,
            'rows' => $rows,
        ]);
    }

    /**
     * Everyone who still owes money — hutang VIP and outstanding DP,
     * combined in one place. Neither had a single "who owes what" view
     * before this; hutang lived only as a running total on CustomerLimit,
     * DP was scattered across the Kasir DP tab.
     */
    public function piutang(): View
    {
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $model::query()
                ->with('customer')
                ->whereIn('status_bayar', ['hutang', 'dp'])
                ->where('jumlah_piutang', '>', 0)
                ->orderBy('dibayar_at')
                ->get()
                ->each(function ($order) use (&$rows, $type) {
                    $rows->push([
                        'type' => $type,
                        'id' => $order->id,
                        'tipe' => ucfirst($type),
                        'no_order' => $order->NoOrder,
                        'customer' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-',
                        'jenis' => $order->status_bayar === 'hutang' ? 'Hutang VIP' : 'DP',
                        'status_bayar' => $order->status_bayar,
                        'total' => (float) $order->total,
                        'jumlah_piutang' => (float) $order->jumlah_piutang,
                        'sejak' => $order->dibayar_at,
                    ]);
                });
        }

        $rows = $rows->sortBy('sejak')->values();

        return view('keuangan.piutang', [
            'rows' => $rows,
            'totalHutang' => $rows->where('status_bayar', 'hutang')->sum('jumlah_piutang'),
            'totalDp' => $rows->where('status_bayar', 'dp')->sum('jumlah_piutang'),
        ]);
    }

    public function totalTagihanPiutang(Request $request): View
    {
        $asOf = $request->filled('tanggal')
            ? $request->string('tanggal')->toString()
            : now()->format('Y-m-d');
        $byCustomer = collect();

        foreach ([OrderIndoor::class, OrderOutdoor::class, OrderArtwork::class] as $model) {
            $model::query()->with('customer')
                ->whereDate('TglOrder', '<=', $asOf)
                ->where('status', '!=', 'batal')
                ->where('jumlah_piutang', '>', 0)
                ->get()
                ->each(function ($order) use (&$byCustomer) {
                    $key = $order->KdCust ?: 'tanpa-customer';
                    $current = $byCustomer->get($key, [
                        'name' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                        'phone' => $order->customer?->Telp ?: '-',
                        'date' => $order->TglOrder,
                        'receivable' => 0.0,
                    ]);
                    if ((string) $order->TglOrder < (string) $current['date']) {
                        $current['date'] = $order->TglOrder;
                    }
                    $current['receivable'] += (float) $order->jumlah_piutang;
                    $byCustomer->put($key, $current);
                });
        }

        $rows = $byCustomer->sortBy(fn (array $row) => mb_strtolower($row['name']))->values();

        return view('keuangan.total-tagihan-piutang', [
            'asOf' => $asOf,
            'rows' => $rows,
            'grandTotal' => (float) $rows->sum('receivable'),
        ]);
    }

    public function creditLimits(): View
    {
        $rows = Customer::query()->whereHas('limit')->with('limit')->orderBy('NmCust')->get()
            ->map(fn (Customer $customer) => (object) [
                'customer' => $customer->NmCust,
                'receivable' => max(0, (float) $customer->limit->Total),
                'limit' => (float) $customer->limit->Batas,
            ]);

        return view('keuangan.credit-limits', [
            'rows' => $rows,
            'totalReceivable' => (float) $rows->sum('receivable'),
            'totalLimit' => (float) $rows->sum('limit'),
        ]);
    }

    public function globalCustomerReceivables(Request $request): View
    {
        $asOf = $request->filled('tanggal')
            ? $request->string('tanggal')->toString()
            : now()->format('Y-m-d');
        $customers = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $settlementOrderIds = OrderPayment::query()->where('order_type', $type)
                ->where('jenis', 'pelunasan_hutang')->pluck('order_id');
            $orders = $model::query()->with('customer')
                ->whereDate('TglOrder', '<=', $asOf)
                ->where('status', '!=', 'batal')
                ->where(function ($query) use ($settlementOrderIds) {
                    $query->where('status_bayar', 'hutang');
                    if ($settlementOrderIds->isNotEmpty()) {
                        $query->orWhereIn('id', $settlementOrderIds);
                    }
                })->get();
            $paymentsByOrder = OrderPayment::query()->where('order_type', $type)
                ->where('jenis', 'pelunasan_hutang')->whereIn('order_id', $orders->pluck('id'))
                ->where('created_at', '<=', "{$asOf} 23:59:59")
                ->get()->groupBy('order_id');

            foreach ($orders as $order) {
                $gross = (float) $order->total;
                $discount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $asOf
                    ? $order->diskonNominal() : 0.0;
                $net = max(0, $gross - $discount);
                $paid = min($net, max(0, (float) ($paymentsByOrder[$order->id] ?? collect())->sum('jumlah')));
                $remaining = max(0, $net - $paid);
                if ($remaining <= 0) {
                    continue;
                }

                $key = $order->KdCust ?: 'tanpa-customer';
                $row = $customers->get($key, [
                    'code' => $order->KdCust,
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'receivable' => 0.0, 'discount' => 0.0, 'paid' => 0.0, 'remaining' => 0.0,
                ]);
                $row['receivable'] += $gross;
                $row['discount'] += $discount;
                $row['paid'] += $paid;
                $row['remaining'] += $remaining;
                $customers->put($key, $row);
            }
        }

        $rows = $customers->sortBy(fn (array $row) => mb_strtolower($row['customer']))->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.global-customer-receivables', compact('asOf', 'rows', 'totals'));
    }

    public function customerReceivableDetails(Request $request): View
    {
        $from = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $customerCode = $request->string('customer')->trim()->toString();
        $customers = Customer::query()->orderBy('NmCust')->get(['KdCust', 'NmCust']);
        $selectedCustomer = $customerCode !== '' ? $customers->firstWhere('KdCust', $customerCode) : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $settledIds = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')->pluck('order_id');
                $orders = $model::query()->where('KdCust', $customerCode)
                    ->whereBetween('TglOrder', [$from, $to])->where('status', '!=', 'batal')
                    ->where(function ($query) use ($settledIds) {
                        $query->where('status_bayar', 'hutang');
                        if ($settledIds->isNotEmpty()) {
                            $query->orWhereIn('id', $settledIds);
                        }
                    })->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $payments = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')
                    ->whereIn('order_id', $orders->pluck('id'))->where('created_at', '<=', "{$to} 23:59:59")
                    ->get()->groupBy('order_id');
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')->get(['order_id', 'number'])
                    ->unique('order_id')->pluck('number', 'order_id');

                foreach ($orders as $order) {
                    $gross = (float) $order->total;
                    $discount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $to
                        ? $order->diskonNominal() : 0.0;
                    $net = max(0, $gross - $discount);
                    $paid = min($net, max(0, (float) ($payments[$order->id] ?? collect())->sum('jumlah')));
                    $remaining = max(0, $net - $paid);
                    if ($remaining <= 0) {
                        continue;
                    }
                    $rows->push((object) [
                        'date' => $order->TglOrder,
                        'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                        'receivable' => $gross, 'discount' => $discount,
                        'paid' => $paid, 'remaining' => $remaining,
                    ]);
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->invoice)->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.customer-receivable-details', compact(
            'from', 'to', 'customers', 'selectedCustomer', 'customerCode', 'rows', 'totals'
        ));
    }

    public function dailyReceivableCollections(Request $request): View
    {
        $date = $request->filled('tanggal') ? $request->string('tanggal')->toString() : now()->format('Y-m-d');
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $settledIds = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')->pluck('order_id');
            $orders = $model::query()->with('customer')->whereDate('TglOrder', '<=', $date)
                ->where('status', '!=', 'batal')->where(function ($query) use ($settledIds) {
                    $query->where('status_bayar', 'hutang');
                    if ($settledIds->isNotEmpty()) {
                        $query->orWhereIn('id', $settledIds);
                    }
                })->get();
            $allPayments = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')
                ->whereIn('order_id', $orders->pluck('id'))->where('created_at', '<=', "{$date} 23:59:59")
                ->orderBy('created_at')->get()->groupBy('order_id');

            foreach ($orders as $order) {
                $payments = $allPayments[$order->id] ?? collect();
                $todayPayments = $payments->filter(fn (OrderPayment $payment) => $payment->created_at?->format('Y-m-d') === $date);
                $gross = (float) $order->total;
                $discount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $date
                    ? $order->diskonNominal() : 0.0;
                $net = max(0, $gross - $discount);
                $paidToDate = min($net, max(0, (float) $payments->sum('jumlah')));
                $paidToday = max(0, (float) $todayPayments->sum('jumlah'));
                $remaining = max(0, $net - $paidToDate);
                if ($remaining <= 0 && $paidToday <= 0) {
                    continue;
                }
                $notes = $todayPayments->map(function (OrderPayment $payment) {
                    $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
                    return $method.($payment->no_referensi ? ' '.$payment->no_referensi : '');
                })->unique()->implode(', ');

                $rows->push((object) [
                    'customer_code' => $order->KdCust ?: '-',
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'date' => $order->TglOrder, 'order' => $order->NoOrder,
                    'receivable' => $gross, 'paid' => $paidToday,
                    'discount' => $discount, 'remaining' => $remaining,
                    'notes' => $notes ?: '-',
                ]);
            }
        }

        $groups = $rows->sortBy(fn ($row) => mb_strtolower($row->customer).'|'.$row->date.'|'.$row->order)
            ->groupBy('customer_code')->map(function ($customerRows) {
                return (object) [
                    'customer' => $customerRows->first()->customer,
                    'rows' => $customerRows->values(),
                    'totalPaid' => (float) $customerRows->sum('paid'),
                ];
            })->sortBy(fn ($group) => mb_strtolower($group->customer))->values();

        return view('keuangan.daily-receivable-collections', [
            'date' => $date, 'groups' => $groups,
            'grandTotalPaid' => (float) $rows->sum('paid'),
        ]);
    }

    /**
     * Laba Rugi (income statement) — every posting the app has made via
     * AccountingService lands in `am` (JurnalEntry) tagged to an account in
     * `am__` (Akun). This just sums Debet/Kredit per L-account (TipeNL='L')
     * within the period and arranges them into the standard Pendapatan −
     * HPP − Biaya Usaha (+ pendapatan/biaya lain-lain) waterfall.
     */
    public function labaRugi(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');

        $akunLabaRugi = Akun::query()->where('TipeNL', 'L')->orderBy('NoAkun')->get();

        $saldo = JurnalEntry::query()
            ->whereIn('NoAkun', $akunLabaRugi->pluck('NoAkun'))
            ->whereBetween('TgTrans', [$dari, $sampai])
            ->select('NoAkun', DB::raw('SUM(Debet) as debet'), DB::raw('SUM(Kredit) as kredit'))
            ->groupBy('NoAkun')
            ->get()
            ->keyBy('NoAkun');

        $biayaLainKodes = ['74000'];

        $buildGroup = function (string $prefix) use ($akunLabaRugi, $saldo, $biayaLainKodes) {
            return $akunLabaRugi->filter(fn (Akun $a) => str_starts_with($a->NoAkun, $prefix))
                ->map(function (Akun $a) use ($saldo, $biayaLainKodes) {
                    $s = $saldo->get($a->NoAkun);
                    $debet = (float) ($s->debet ?? 0);
                    $kredit = (float) ($s->kredit ?? 0);
                    $isExpenseLike = $a->TipeDK === 'D' || in_array($a->NoAkun, $biayaLainKodes, true);

                    return [
                        'akun' => $a->NoAkun,
                        'nama' => $a->NmAkun,
                        'debet' => $debet,
                        'kredit' => $kredit,
                        'saldo' => $isExpenseLike ? $debet - $kredit : $kredit - $debet,
                    ];
                })
                ->values();
        };

        $pendapatan = $buildGroup('4');
        $hpp = $buildGroup('5');
        $biayaUsaha = $buildGroup('6');
        $pendapatanLain = $buildGroup('7')->reject(fn ($r) => in_array($r['akun'], $biayaLainKodes, true))->values();
        $biayaLain = $buildGroup('7')->filter(fn ($r) => in_array($r['akun'], $biayaLainKodes, true))->values();

        $totalPendapatan = $pendapatan->sum('saldo');
        $totalHpp = $hpp->sum('saldo');
        $labaKotor = $totalPendapatan - $totalHpp;
        $totalBiayaUsaha = $biayaUsaha->sum('saldo');
        $labaUsaha = $labaKotor - $totalBiayaUsaha;
        $totalPendapatanLain = $pendapatanLain->sum('saldo');
        $totalBiayaLain = $biayaLain->sum('saldo');
        $labaBersih = $labaUsaha + $totalPendapatanLain - $totalBiayaLain;

        return view('keuangan.laba-rugi', [
            'dari' => $dari,
            'sampai' => $sampai,
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'biayaUsaha' => $biayaUsaha,
            'pendapatanLain' => $pendapatanLain,
            'biayaLain' => $biayaLain,
            'totalPendapatan' => $totalPendapatan,
            'totalHpp' => $totalHpp,
            'labaKotor' => $labaKotor,
            'totalBiayaUsaha' => $totalBiayaUsaha,
            'labaUsaha' => $labaUsaha,
            'totalPendapatanLain' => $totalPendapatanLain,
            'totalBiayaLain' => $totalBiayaLain,
            'labaBersih' => $labaBersih,
        ]);
    }

    /**
     * PPN Keluaran — every order that reached 'lunas' in the period, in
     * full, no exclusions. Assumes displayed prices already include PPN
     * (standard retail practice here), so DPP = total / (1 + rate) and
     * PPN = total - DPP. Rate is adjustable in case the applicable rate
     * changes or differs per case; nothing about which rows appear is
     * user-selectable — this is a complete report of realized sales.
     */
    public function laporanPpn(Request $request): View
    {
        [$dari, $sampai, $rate, $rows] = $this->ppnData($request);
        $periode = substr($dari, 0, 7);
        $laporanFinal = LaporanPpnFinal::with('items')->where('periode', $periode)->first();

        // A locked report is a historical snapshot: later edits to an order
        // or changing the default PPN rate must never alter its contents.
        if ($laporanFinal?->status === 'final') {
            $rate = (float) $laporanFinal->tarif_ppn;
            $rows = $laporanFinal->items->map(fn ($item) => [
                'id' => $item->order_id,
                'type_key' => $item->order_type,
                'key' => $item->order_type.'-'.$item->order_id,
                'tanggal' => $item->tanggal_lunas,
                'tipe' => ucfirst($item->order_type),
                'no_order' => $item->no_order,
                'customer' => $item->customer ?: '-',
                'total' => $item->total,
                'dpp' => $item->dpp,
                'ppn' => $item->ppn,
            ])->values();
        }

        return view('keuangan.laporan-ppn', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rate' => $rate,
            'rows' => $rows,
            'totalOmzet' => $rows->sum('total'),
            'totalDpp' => $rows->sum('dpp'),
            'totalPpn' => $rows->sum('ppn'),
            'periode' => $periode,
            'laporanFinal' => $laporanFinal,
            'selectedKeys' => $laporanFinal?->items->map(fn ($item) => $item->order_type.'-'.$item->order_id)->all() ?? [],
        ]);
    }

    public function simpanDraftPpn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dari' => ['required', 'date'],
            'sampai' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string'],
        ]);

        $periode = substr($data['dari'], 0, 7);
        abort_unless(substr($data['sampai'], 0, 7) === $periode, 422, 'Draft PPN harus dibuat untuk satu bulan yang sama.');

        [$dari, $sampai, $rate, $rows] = $this->ppnData(Request::create('/', 'GET', $data));
        $rowsByKey = $rows->keyBy('key');
        $selectedRows = collect($data['selected'] ?? [])->unique()->map(fn ($key) => $rowsByKey->get($key))->filter();

        $report = LaporanPpnFinal::firstOrCreate(
            ['periode' => $periode],
            ['tarif_ppn' => $rate, 'status' => 'draft', 'created_by' => auth()->id()],
        );
        abort_if($report->status === 'final', 422, 'Laporan PPN Final sudah dikunci dan tidak dapat diubah.');

        DB::transaction(function () use ($report, $rate, $selectedRows) {
            $report = LaporanPpnFinal::lockForUpdate()->findOrFail($report->id);
            abort_if($report->status === 'final', 422, 'Laporan sudah final.');
            $report->update(['tarif_ppn' => $rate]);
            $report->items()->delete();
            $report->items()->createMany($selectedRows->map(fn ($row) => [
                'order_type' => $row['type_key'], 'order_id' => $row['id'],
                'tanggal_lunas' => $row['tanggal'], 'no_order' => $row['no_order'],
                'customer' => $row['customer'], 'total' => $row['total'], 'dpp' => $row['dpp'], 'ppn' => $row['ppn'],
            ])->all());
        });

        return redirect()->route('keuangan.laporan-ppn', compact('dari', 'sampai', 'rate'))->with('status', 'Draft Laporan PPN disimpan.');
    }

    public function finalkanPpn(LaporanPpnFinal $laporanPpnFinal): RedirectResponse
    {
        DB::transaction(function () use ($laporanPpnFinal) {
            $report = LaporanPpnFinal::lockForUpdate()->findOrFail($laporanPpnFinal->id);
            abort_if($report->status === 'final', 422, 'Laporan ini sudah dikunci.');
            abort_unless($report->items()->exists(), 422, 'Pilih minimal satu transaksi.');
            foreach ($report->items as $item) {
                $invoice = app(OrderDocumentService::class)->invoiceQuery()
                    ->where('order_type', $item->order_type)->where('order_id', $item->order_id)->first();
                abort_unless($invoice && $invoice->number === $item->no_order
                    && substr($invoice->issued_at, 0, 7) === $report->periode
                    && abs((float) $invoice->total - (float) $item->total) < 0.01,
                    422, 'Draft tidak sesuai invoice aktif. Simpan ulang draft sebelum finalisasi.');
            }
            $report->update(['status' => 'final', 'finalized_at' => now(), 'finalized_by' => auth()->id()]);
        });

        return redirect()->route('keuangan.laporan-ppn', ['dari' => $laporanPpnFinal->periode.'-01', 'sampai' => Carbon::parse($laporanPpnFinal->periode.'-01')->endOfMonth()->format('Y-m-d'), 'rate' => $laporanPpnFinal->tarif_ppn])->with('status', 'Laporan PPN Final dikunci sebagai histori.');
    }

    /**
     * CSV recap of the same PPN report — a plain accounting export (No,
     * Tanggal, No Order, Customer, DPP, PPN, Total) meant to hand to an
     * accountant or paste into a spreadsheet for SPT Masa PPN prep. This is
     * NOT a DJP e-Faktur bulk-import file — that format requires each
     * buyer's NPWP/address, which this app doesn't collect.
     */
    public function exportPpn(Request $request): StreamedResponse
    {
        [$dari, $sampai, $rate, $rows] = $this->ppnData($request);
        $laporanFinal = LaporanPpnFinal::with('items')->where('periode', substr($dari, 0, 7))->where('status', 'final')->first();

        if ($laporanFinal) {
            $rate = (float) $laporanFinal->tarif_ppn;
            $rows = $laporanFinal->items->map(fn ($item) => [
                'tanggal' => $item->tanggal_lunas, 'tipe' => ucfirst($item->order_type),
                'no_order' => $item->no_order, 'customer' => $item->customer ?: '-',
                'total' => $item->total, 'dpp' => $item->dpp, 'ppn' => $item->ppn,
            ]);
        }

        $filename = "laporan-ppn_{$dari}_{$sampai}.csv";

        return response()->streamDownload(function () use ($dari, $sampai, $rate, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders "Rp"/accented names correctly

            fputcsv($out, ["Laporan PPN Keluaran {$dari} s/d {$sampai} (tarif {$rate}%)"]);
            fputcsv($out, []);
            fputcsv($out, ['No', 'Tanggal Lunas', 'Tipe', 'No Order', 'Customer', 'Total', 'DPP', 'PPN']);

            foreach ($rows as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    $row['tanggal']?->format('Y-m-d'),
                    $row['tipe'],
                    $row['no_order'],
                    $row['customer'],
                    number_format($row['total'], 2, '.', ''),
                    number_format($row['dpp'], 2, '.', ''),
                    number_format($row['ppn'], 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', 'Total', number_format($rows->sum('total'), 2, '.', ''), number_format($rows->sum('dpp'), 2, '.', ''), number_format($rows->sum('ppn'), 2, '.', '')]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: string, 1: string, 2: float, 3: Collection}
     */
    private function ppnData(Request $request): array
    {
        $periode = $request->string('periode')->toString();
        $isMonthlyPeriod = preg_match('/^\d{4}-\d{2}$/', $periode) === 1;
        $dari = $isMonthlyPeriod ? $periode.'-01' : ($request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d'));
        $sampai = $isMonthlyPeriod ? Carbon::parse($dari)->endOfMonth()->format('Y-m-d') : ($request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d'));
        $rate = $request->filled('rate') ? (float) $request->input('rate') : PengaturanKeuangan::current()->tarif_ppn_default;

        $rows = collect();

        app(OrderDocumentService::class)->invoiceQuery()
            ->whereBetween('issued_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->get()->each(function ($invoice) use (&$rows, $rate) {
                $snapshot = json_decode($invoice->snapshot, true);
                $total = (float) $invoice->total;
                $dpp = $rate > 0 ? $total / (1 + $rate / 100) : $total;
                $rows->push([
                    'id' => $invoice->order_id, 'type_key' => $invoice->order_type,
                    'key' => $invoice->order_type.'-'.$invoice->order_id,
                    'tanggal' => Carbon::parse($invoice->issued_at), 'tipe' => ucfirst($invoice->order_type),
                    'no_order' => $invoice->number, 'customer' => ($snapshot['customer'] ?? null) ?: '-',
                    'total' => $total, 'dpp' => $dpp, 'ppn' => $total - $dpp,
                ]);
            });

        return [$dari, $sampai, $rate, $rows->sortBy('tanggal')->values()];
    }
}
