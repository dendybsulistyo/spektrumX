<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\FinalSalesDiscount;
use App\Models\HargaCetakOutdoor;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\PrinterOutdoor;
use App\Services\OrderDocumentService;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly OrderPricingService $pricing,
        private readonly OrderDocumentService $documents,
    ) {}

    public function priceListOutdoor(): View
    {
        $printers = PrinterOutdoor::orderBy('NoUrut')->get();
        $bahans = BahanCetakOutdoor::orderBy('NoUrut')->get();
        $hargas = HargaCetakOutdoor::all()->keyBy('KdCtk');

        $tiers = [
            ['label' => '3 - 6 Hari', 'multiplier' => 1.0],
            ['label' => '7 Hari', 'multiplier' => 0.95],
            ['label' => '12 Hari', 'multiplier' => 0.90],
            ['label' => '>12 Hari', 'multiplier' => 0.85],
        ];

        return view('reports.price-list-outdoor', compact('printers', 'bahans', 'hargas', 'tiers'));
    }

    public function dailyTransactions(Request $request): View
    {
        $date = $request->date('tanggal')?->format('Y-m-d') ?? now()->format('Y-m-d');
        $kind = in_array($request->query('jenis'), ['lunas', 'tagihan', 'pelunasan'], true) ? $request->query('jenis') : null;
        if ($kind === 'pelunasan') {
            return $this->dailySettlements($date);
        }
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $relations = $type === 'outdoor' ? ['customer.limit', 'items.hargaCetak'] : ['customer.limit', 'items'];
            $orders = $model::query()
                ->with($relations)
                ->where('TglOrder', $date)
                ->where('status', '!=', 'batal')
                ->where(function ($query) use ($kind): void {
                    if ($kind !== 'tagihan') {
                        $query->where('status_bayar', 'lunas');
                    }
                    if ($kind !== 'lunas') {
                        $query->orWhere(function ($vipDebt): void {
                            $vipDebt->where('status_bayar', 'hutang')
                                ->whereHas('customer.limit');
                        });
                    }
                })
                ->orderBy('NoOrder')
                ->get();

            if ($orders->isEmpty()) {
                continue;
            }

            $documents = DB::table('order_documents')
                ->where('order_type', $type)
                ->whereIn('order_id', $orders->pluck('id'))
                ->where('kind', 'inv')
                ->orderBy('sequence')
                ->get(['order_id', 'kind', 'number'])
                ->groupBy('order_id');
            $payments = OrderPayment::query()
                ->where('order_type', $type)
                ->whereIn('order_id', $orders->pluck('id'))
                ->get(['order_id', 'jumlah'])
                ->groupBy('order_id');

            $orders->each(function ($order) use ($type, $documents, $payments, &$rows) {
                $orderDocuments = $documents->get($order->id, collect());
                $items = $this->pricing->detailedLineItems($type, $order, $order->items);
                $gross = (float) $items->sum('subtotal');
                $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                $discount = max(0, $gross - $net);
                $recordedPayment = (float) $payments->get($order->id, collect())->sum('jumlah');
                $paid = $order->status_bayar === 'lunas' && $recordedPayment <= 0
                    ? $net
                    : max(0, min($net, $recordedPayment));
                $credit = max(0, $net - $paid);

                if ($items->isEmpty()) {
                    $items = collect([(object) ['name' => '-', 'bahan' => '-', 'printer' => null,
                        'panjang' => 0, 'lebar' => 0, 'qty' => 0, 'harga_satuan' => null,
                        'subtotal' => $gross, 'breakdown' => null]]);
                }

                $allocatedDiscount = 0.0;
                $allocatedPaid = 0.0;
                $allocatedCredit = 0.0;
                foreach ($items->values() as $index => $item) {
                    $last = $index === $items->count() - 1;
                    $ratio = $gross > 0 ? (float) $item->subtotal / $gross : ($last ? 1 : 0);
                    $lineDiscount = $last ? $discount - $allocatedDiscount : round($discount * $ratio);
                    $linePaid = $last ? $paid - $allocatedPaid : round($paid * $ratio);
                    $lineCredit = $last ? $credit - $allocatedCredit : round($credit * $ratio);
                    $allocatedDiscount += $lineDiscount;
                    $allocatedPaid += $linePaid;
                    $allocatedCredit += $lineCredit;

                    $rows->push((object) [
                        'date' => $order->TglOrder, 'number' => $order->NoOrder,
                        'invoices' => $orderDocuments->isNotEmpty()
                            ? $orderDocuments->pluck('number')->values()
                            : collect([$order->NoOrder]),
                        'customer' => $order->customer?->NmCust ?? '-',
                        'note_status' => $order->status_bayar === 'hutang' ? 'Tagihan VIP' : 'Lunas',
                        'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: '-',
                        'description' => collect([$item->name, $item->breakdown])->filter()->implode(' — '),
                        'length' => $item->panjang, 'width' => $item->lebar, 'qty' => $item->qty,
                        'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                        'discount' => $lineDiscount, 'total' => (float) $item->subtotal - $lineDiscount,
                        'cash' => $linePaid, 'credit' => $lineCredit,
                    ]);
                }
            });
        }

        $rows = $rows->sortBy(fn ($row) => $row->number.'|'.$row->description)->values();

        return view('reports.daily-transactions', [
            'date' => $date, 'rows' => $rows, 'kind' => $kind,
            // Tabel pelunasan sedang disembunyikan di view; aktifkan lagi bersama view-nya.
            // ...($kind === null ? $this->settlementData($date) : []),
            'totals' => (object) collect(['subtotal', 'discount', 'total', 'cash', 'credit'])
                ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all(),
        ]);
    }

    /**
     * Rekap - Pelunasan: pembayaran pelunasan (hutang VIP & sisa DP) yang
     * diterima pada tanggal tersebut, apa pun tanggal order-nya.
     */
    private function dailySettlements(string $date): View
    {
        return view('reports.daily-settlements', ['date' => $date, ...$this->settlementData($date)]);
    }

    /** @return array{settlementRows: \Illuminate\Support\Collection, settlementTotal: float, byMethod: \Illuminate\Support\Collection} */
    private function settlementData(string $date): array
    {
        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $payments = OrderPayment::query()
            ->whereIn('jenis', ['pelunasan_hutang', 'pelunasan_dp'])
            ->where('jumlah', '>', 0)
            ->whereDate('created_at', $date)
            ->oldest('created_at')->oldest('id')
            ->get();

        $rows = $payments->groupBy('order_type')->flatMap(function ($group, $type) use ($models) {
            $model = $models[$type] ?? null;
            if (! $model) {
                return [];
            }
            $orders = $model::query()->with('customer')->whereIn('id', $group->pluck('order_id'))->get()->keyBy('id');
            $invoices = DB::table('order_documents')
                ->where('order_type', $type)->where('kind', 'inv')->whereIn('order_id', $group->pluck('order_id'))
                ->orderByDesc('sequence')->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');

            return $group->map(function ($payment) use ($orders, $invoices) {
                $order = $orders->get($payment->order_id);
                $total = $order ? ($order->diskonStatus() === 'approved' ? (float) $order->totalSetelahDiskon() : (float) $order->total) : 0.0;

                return (object) [
                    'paid_at' => $payment->created_at,
                    'invoice' => $invoices[$payment->order_id] ?? $order?->NoOrder ?? '-',
                    'order' => $order?->NoOrder ?? '-',
                    'order_date' => $order?->TglOrder,
                    'customer' => $order?->customer?->NmCust ?? '-',
                    'kind' => OrderPayment::JENIS_LABELS[$payment->jenis] ?? $payment->jenis,
                    'method' => OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? strtoupper((string) $payment->cara_bayar),
                    'reference' => $payment->no_referensi,
                    'total' => $total,
                    'amount' => (float) $payment->jumlah,
                    'remaining' => $order ? max(0, (float) $order->jumlah_piutang) : 0.0,
                ];
            });
        })->sortBy(fn ($row) => $row->paid_at?->timestamp)->values();

        return [
            'settlementRows' => $rows,
            'settlementTotal' => (float) $rows->sum('amount'),
            'byMethod' => $rows->groupBy('method')->map(fn ($group) => (float) $group->sum('amount')),
        ];
    }

    public function podTurnover(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $rows = DB::table('order_indoor_detail as detail')
            ->join('order_indoor as orders', 'orders.id', '=', 'detail.order_indoor_id')
            // Hanya divisi 03 "Paper/Media POD" (kertas).
            ->whereIn('detail.KdProd', DB::table('produk_indoor')->where('KdDivs', '03')->select('KdProd'))
            ->whereBetween('orders.TglOrder', [$from, $to])
            ->where('orders.status', '!=', 'batal')
            ->selectRaw("COALESCE(NULLIF(TRIM(detail.NmProd), ''), NULLIF(TRIM(detail.Judul), ''), detail.KdProd) AS product")
            ->selectRaw('SUM(detail.Qty) AS quantity')
            ->groupBy('product')->orderBy('product')->get();

        return view('reports.pod-turnover', [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'total' => (float) $rows->sum('quantity'),
        ]);
    }

    public function ctpTurnover(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $details = DB::table('order_indoor_detail as detail')
            ->join('order_indoor as orders', 'orders.id', '=', 'detail.order_indoor_id')
            ->whereBetween('orders.TglOrder', [$from, $to])
            ->where('orders.status', '!=', 'batal')
            ->whereRaw("LOWER(detail.NmProd) LIKE '%warna%'")
            ->get(['orders.id as order_id', 'orders.TglOrder as date', 'detail.NmProd as product']);

        $rows = $details->map(function ($detail) {
            preg_match('/(\d+)\s*warna/i', $detail->product, $match);

            return (object) [
                'order_id' => (int) $detail->order_id,
                'date' => $detail->date,
                'product' => trim($detail->product),
                'colors' => max(1, (int) ($match[1] ?? 1)),
            ];
        })->groupBy(fn ($row) => $row->date.'|'.mb_strtolower($row->product))
            ->map(function ($group) {
                $first = $group->first();
                $orders = $group->pluck('order_id')->unique()->count();

                return (object) [
                    'date' => $first->date,
                    'product' => $first->product,
                    'colors' => $first->colors,
                    'orders' => $orders,
                    'plates' => $orders * $first->colors,
                ];
            })->sortBy(fn ($row) => $row->date.'|'.$row->product)->values();

        return view('reports.ctp-turnover', [
            'from' => $from, 'to' => $to, 'rows' => $rows,
            'totalOrders' => (int) $rows->sum('orders'),
            'totalPlates' => (int) $rows->sum('plates'),
        ]);
    }

    public function outdoorOrders(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $selectedType = in_array($request->query('jenis'), ['indoor', 'outdoor'], true)
            ? $request->query('jenis')
            : 'indoor';
        // Status dokumen, tidak tumpang tindih (Pre Order + Order + Invoice = seluruh order):
        // - preorder: belum dibayar di kasir dan belum ada nomor Invoice
        // - order   : sudah diproses kasir (lunas/DP/hutang), belum ada nomor Invoice
        // - invoice : sudah punya nomor Invoice
        // Pilihan "Semua" sengaja tidak disediakan; bawaan Pre Order.
        $statusOptions = ['preorder' => 'Pre Order', 'order' => 'Order', 'invoice' => 'Invoice'];
        $selectedStatus = array_key_exists((string) $request->query('status'), $statusOptions)
            ? (string) $request->query('status')
            : 'preorder';
        $printAll = $request->boolean('semua_halaman');

        $groups = collect();

        // Order batal hanya ditampilkan bila masih ada uang yang TIDAK
        // dikembalikan (sisa pembatalan = pendapatan di jurnal). Nilainya
        // = jumlah_dibayar yang tersisa. Nota yang dihanguskan untuk nota
        // pengganti tidak ikut karena uangnya pindah ke nota pengganti.
        $isRetainedCancel = fn ($order) => $order->status === 'batal';
        $orderTotal = fn ($order) => $isRetainedCancel($order)
            ? max(0, (float) $order->jumlah_dibayar)
            : ($order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total);
        $orderPaid = fn ($order) => min($orderTotal($order), max(0, (float) $order->jumlah_dibayar));

        foreach ([$selectedType => $selectedType === 'indoor' ? OrderIndoor::class : OrderOutdoor::class] as $type => $model) {
            $relations = $type === 'indoor'
                ? ['customer', 'createdBy', 'items']
                : ['customer', 'createdBy', 'items.hargaCetak'];
            $table = (new $model)->getTable();
            $hasInvoice = fn ($query) => $query->selectRaw('1')->from('order_documents')
                ->where('kind', 'inv')->where('order_type', $type)
                ->whereColumn('order_id', $table.'.id');
            $baseQuery = $model::query()
                ->whereBetween('TglOrder', [$from, $to])
                ->where(fn ($query) => $query->where('status', '!=', 'batal')
                    ->orWhere(fn ($cancelled) => $cancelled->where('status', 'batal')
                        ->whereNull('invoice_voided_at')
                        ->where('jumlah_dibayar', '>', 0)))
                ->when($selectedStatus === 'invoice', fn ($query) => $query->whereExists($hasInvoice))
                ->when($selectedStatus === 'order', fn ($query) => $query->whereNotExists($hasInvoice)->where('status_bayar', '!=', 'belum_bayar'))
                ->when($selectedStatus === 'preorder', fn ($query) => $query->whereNotExists($hasInvoice)->where('status_bayar', 'belum_bayar'));
            $listQuery = (clone $baseQuery)->with($relations)->orderBy('TglOrder')->orderBy('NoOrder');
            $paginator = $printAll ? null : $listQuery->paginate(100)->withQueryString();
            $orders = $printAll ? $listQuery->get() : collect($paginator->items());
            $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
                ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');
            $rows = collect();

            foreach ($orders as $order) {
                $rawItems = $type === 'indoor' ? $order->detailItems() : $order->items;
                $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                if ($items->isEmpty()) {
                    $items = collect([(object) ['name' => '-', 'bahan' => '-', 'printer' => '-',
                        'panjang' => 0, 'lebar' => 0, 'qty' => 0]]);
                }
                $total = $orderTotal($order);
                $advance = $orderPaid($order);
                $status = $isRetainedCancel($order) ? 'Batal (sisa)' : match ($order->status_bayar) {
                    'lunas' => 'Lunas', 'dp' => 'DP', 'hutang' => 'Hutang', default => 'Pre Order',
                };

                foreach ($items->values() as $index => $item) {
                    $rows->push((object) [
                        'first' => $index === 0, 'date' => $order->TglOrder, 'order' => $order->NoOrder,
                        'invoice' => $invoiceNumbers[$order->id] ?? '-',
                        'operator' => $order->createdBy?->name ?? '-', 'customer' => $order->customer?->NmCust ?? '-',
                        'product' => collect([$item->printer, $item->bahan])->filter(fn ($v) => $v && $v !== '-')->implode(' / ') ?: '-',
                        'title' => $item->name ?: '-', 'length' => $item->panjang, 'width' => $item->lebar,
                        'qty' => $item->qty, 'total' => $total, 'advance' => $advance, 'status' => $status,
                    ]);
                }
            }

            // Total seluruh periode (semua halaman) — rumus sama dengan baris
            // per order, tetapi hanya memuat kolom yang diperlukan.
            $periodTotal = 0.0;
            $periodPaid = 0.0;
            $periodCount = 0;
            (clone $baseQuery)
                ->select(['id', 'status', 'total', 'jumlah_dibayar', 'diskon_tipe', 'diskon_persen', 'diskon_nominal_tetap', 'diskon_akhir_nominal',
                    'diskon_approved_at', 'diskon_rejected_at', 'diskon_requested_at'])
                ->lazyById(1000)
                ->each(function ($order) use (&$periodTotal, &$periodPaid, &$periodCount, $orderTotal, $orderPaid) {
                    $periodTotal += $orderTotal($order);
                    $periodPaid += $orderPaid($order);
                    $periodCount++;
                });

            $groups->put($type, (object) [
                'label' => ucfirst($type),
                'rows' => $rows,
                'paginator' => $paginator,
                'periodTotal' => $periodTotal,
                'periodPaid' => $periodPaid,
                'periodCount' => $periodCount,
                'grandTotal' => (float) $orders->sum($orderTotal),
                'totalAdvance' => (float) $orders->sum($orderPaid),
            ]);
        }

        return view('reports.outdoor-orders', compact('from', 'to', 'selectedType', 'groups', 'statusOptions', 'selectedStatus', 'printAll'));
    }

    public function allOrdersByCustomer(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $customerCode = $request->string('customer')->trim()->toString();
        $paymentStatus = $request->string('status_bayar')->trim()->toString();
        $allowedStatuses = ['belum_bayar', 'dp', 'hutang', 'lunas'];
        if (! in_array($paymentStatus, $allowedStatuses, true)) {
            $paymentStatus = '';
        }

        $customers = Customer::query()->with('limit')->orderBy('NmCust')->get();
        $selectedCustomer = $customerCode !== '' ? $customers->firstWhere('KdCust', $customerCode) : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $orders = $model::query()
                    ->where('KdCust', $customerCode)
                    ->where('status', '!=', 'batal')
                    ->whereBetween('TglOrder', [$from, $to])
                    ->when($paymentStatus !== '', fn ($query) => $query->where('status_bayar', $paymentStatus))
                    ->orderBy('TglOrder')->orderBy('NoOrder')->get();

                $invoices = DB::table('order_documents')
                    ->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->orderByDesc('sequence')->get(['order_id', 'number', 'issued_at'])
                    ->unique('order_id')->keyBy('order_id');

                foreach ($orders as $order) {
                    $invoice = $invoices->get($order->id);
                    $total = $order->diskonStatus() === 'approved'
                        ? (float) $order->totalSetelahDiskon()
                        : (float) $order->total;
                    $paid = max(0, (float) $order->jumlah_dibayar);
                    $remaining = $order->status_bayar === 'lunas'
                        ? 0.0
                        : max((float) $order->jumlah_piutang, $total - $paid, 0);

                    $rows->push((object) [
                        'date' => $order->TglOrder,
                        'type' => ucfirst($type),
                        'order' => $order->NoOrder,
                        'invoice' => $invoice?->number,
                        'invoice_date' => $invoice?->issued_at,
                        'status' => $order->status_bayar,
                        'total' => $total,
                        'paid' => $paid,
                        'remaining' => $remaining,
                    ]);
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->order)->values();
        $totals = (object) [
            'total' => (float) $rows->sum('total'),
            'paid' => (float) $rows->sum('paid'),
            'remaining' => (float) $rows->sum('remaining'),
        ];

        return view('reports.all-orders-by-customer', compact(
            'from', 'to', 'customers', 'selectedCustomer', 'customerCode', 'paymentStatus', 'rows', 'totals'
        ));
    }

    public function paidOrdersByCustomer(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $customerCode = $request->string('customer')->trim()->toString();
        $customers = Customer::orderBy('NmCust')->get(['KdCust', 'NmCust']);
        $selectedCustomer = $customerCode !== '' ? $customers->firstWhere('KdCust', $customerCode) : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $relations = $type === 'outdoor' ? ['items.hargaCetak'] : ['items'];
                $orders = $model::query()->with($relations)->where('KdCust', $customerCode)->where('status_bayar', 'lunas')
                    ->where('status', '!=', 'batal')->whereBetween('TglOrder', [$from, $to])
                    ->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
                    ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');
                $paymentsByOrder = OrderPayment::query()->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->get()->groupBy('order_id');

                foreach ($orders as $order) {
                    $rawItems = $order->items;
                    $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                    $gross = (float) $items->sum('subtotal');
                    $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $discount = max(0, $gross - $net);
                    $payments = $paymentsByOrder->get($order->id, collect());
                    $advance = max(0, (float) $payments->whereIn('jenis', ['lunas', 'dp', 'nota_pengganti'])->sum('jumlah'));
                    $settlement = max(0, (float) $payments->whereIn('jenis', ['pelunasan_dp', 'pelunasan_hutang'])->sum('jumlah'));
                    if ($advance + $settlement <= 0) {
                        $advance = $net;
                    }

                    $allocated = ['discount' => 0.0, 'advance' => 0.0, 'settlement' => 0.0];
                    foreach ($items->values() as $index => $item) {
                        $last = $index === $items->count() - 1;
                        $ratio = $gross > 0 ? (float) $item->subtotal / $gross : ($last ? 1 : 0);
                        $part = [];
                        foreach (['discount' => $discount, 'advance' => $advance, 'settlement' => $settlement] as $key => $amount) {
                            $part[$key] = $last ? $amount - $allocated[$key] : round($amount * $ratio);
                            $allocated[$key] += $part[$key];
                        }
                        $rows->push((object) [
                            'date' => $order->TglOrder, 'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                            'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: $item->name,
                            'description' => $item->name, 'length' => $item->panjang, 'width' => $item->lebar,
                            'qty' => $item->qty, 'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                            'discount' => $part['discount'], 'total' => (float) $item->subtotal - $part['discount'],
                            'advance' => $part['advance'], 'payment' => $part['settlement'], 'credit' => 0,
                        ]);
                    }
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->invoice.'|'.$row->description)->values();
        $totals = (object) collect(['subtotal', 'discount', 'total', 'advance', 'payment', 'credit'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('reports.paid-orders-by-customer', compact(
            'from', 'to', 'customers', 'selectedCustomer', 'customerCode', 'rows', 'totals'
        ));
    }

    public function creditOrdersByCustomer(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $customerCode = $request->string('customer')->trim()->toString();
        $customers = Customer::query()->whereHas('limit')->with('limit')->orderBy('NmCust')->get();
        $selectedCustomer = $customerCode !== '' ? $customers->firstWhere('KdCust', $customerCode) : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $relations = $type === 'outdoor' ? ['items.hargaCetak'] : ['items'];
                $orders = $model::query()->with($relations)->where('KdCust', $customerCode)->where('status_bayar', 'hutang')
                    ->where('jumlah_piutang', '>', 0)->where('status', '!=', 'batal')
                    ->whereBetween('TglOrder', [$from, $to])->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
                    ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');

                foreach ($orders as $order) {
                    $rawItems = $order->items;
                    $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                    $gross = (float) $items->sum('subtotal');
                    $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $discount = max(0, $gross - $net);
                    // Hutang orders never take a DP; jumlah_dibayar holds the
                    // installments already paid against the receivable.
                    $payment = max(0, (float) $order->jumlah_dibayar);
                    $credit = max(0, (float) $order->jumlah_piutang);
                    $allocated = ['discount' => 0.0, 'payment' => 0.0, 'credit' => 0.0];

                    foreach ($items->values() as $index => $item) {
                        $last = $index === $items->count() - 1;
                        $ratio = $gross > 0 ? (float) $item->subtotal / $gross : ($last ? 1 : 0);
                        $part = [];
                        foreach (['discount' => $discount, 'payment' => $payment, 'credit' => $credit] as $key => $amount) {
                            $part[$key] = $last ? $amount - $allocated[$key] : round($amount * $ratio);
                            $allocated[$key] += $part[$key];
                        }
                        $rows->push((object) [
                            'date' => $order->TglOrder, 'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                            'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: $item->name,
                            'description' => $item->name, 'length' => $item->panjang, 'width' => $item->lebar,
                            'qty' => $item->qty, 'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                            'discount' => $part['discount'], 'total' => (float) $item->subtotal - $part['discount'],
                            'advance' => 0, 'payment' => $part['payment'], 'credit' => $part['credit'],
                        ]);
                    }
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->invoice.'|'.$row->description)->values();
        $totals = (object) collect(['subtotal', 'discount', 'total', 'advance', 'payment', 'credit'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('reports.credit-orders-by-customer', compact(
            'from', 'to', 'customers', 'selectedCustomer', 'customerCode', 'rows', 'totals'
        ));
    }

    public function uninvoicedOrders(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $rows = collect();
        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $table = (new $model)->getTable();
            $relations = $type === 'outdoor'
                ? ['customer', 'createdBy', 'items.hargaCetak']
                : ['customer', 'createdBy', 'items'];
            $orders = $model::query()->with($relations)
                ->whereBetween('TglOrder', [$from, $to])
                ->where('status', '!=', 'batal')
                ->whereNull('invoice_voided_at')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('order_documents')
                    ->where('kind', 'inv')->where('order_type', $type)
                    ->whereColumn('order_id', $table.'.id'))
                ->orderBy('TglOrder')->orderBy('NoOrder')->get();
            $documents = DB::table('order_documents')
                ->where('order_type', $type)
                ->whereIn('order_id', $orders->pluck('id'))
                ->whereIn('kind', ['do', 'inv'])
                ->orderBy('sequence')
                ->get(['order_id', 'kind', 'number'])
                ->groupBy('order_id');

            foreach ($orders as $order) {
                $orderDocuments = $documents->get($order->id, collect());
                $rawItems = $order->items;
                $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                if ($items->isEmpty()) {
                    $items = collect([(object) ['name' => '-', 'bahan' => '-', 'printer' => null,
                        'panjang' => 0, 'lebar' => 0, 'qty' => 0, 'harga_satuan' => null, 'subtotal' => 0]]);
                }

                $gross = (float) $items->sum('subtotal');
                $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                $discount = max(0, $gross - $net);
                $advance = max(0, min($net, (float) $order->jumlah_dibayar));
                $allocated = ['discount' => 0.0, 'advance' => 0.0];

                foreach ($items->values() as $index => $item) {
                    $last = $index === $items->count() - 1;
                    $ratio = $gross > 0 ? (float) $item->subtotal / $gross : ($last ? 1 : 0);
                    $lineDiscount = $last ? $discount - $allocated['discount'] : round($discount * $ratio);
                    $lineAdvance = $last ? $advance - $allocated['advance'] : round($advance * $ratio);
                    $allocated['discount'] += $lineDiscount;
                    $allocated['advance'] += $lineAdvance;

                    $rows->push((object) [
                        'date' => $order->TglOrder, 'order' => $order->NoOrder,
                        'sales_order' => $this->documents->number($order, 'so'),
                        'delivery_orders' => $orderDocuments->where('kind', 'do')->pluck('number')->values(),
                        'invoices' => $orderDocuments->where('kind', 'inv')->pluck('number')->values(),
                        'customer' => $order->customer?->NmCust ?? '-',
                        'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: $item->name,
                        'description' => $item->name, 'recipient' => $order->createdBy?->name ?? '-',
                        'length' => $item->panjang, 'width' => $item->lebar, 'qty' => $item->qty,
                        'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                        'discount' => $lineDiscount, 'total' => (float) $item->subtotal - $lineDiscount,
                        'advance' => $lineAdvance,
                    ]);
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->order.'|'.$row->description)->values();
        $totals = (object) collect(['subtotal', 'discount', 'total', 'advance'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('reports.uninvoiced-orders', compact('from', 'to', 'rows', 'totals'));
    }

    public function salesDiscounts(Request $request): View
    {
        $from = $request->date('dari')?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->date('sampai')?->format('Y-m-d') ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $rows = collect();
        foreach ([OrderIndoor::class, OrderOutdoor::class, OrderArtwork::class] as $model) {
            $model::query()->with('customer')
                ->whereBetween('TglOrder', [$from, $to])
                ->where('status', '!=', 'batal')
                ->whereNotNull('diskon_approved_at')
                ->orderBy('TglOrder')->orderBy('NoOrder')->get()
                ->each(function ($order) use (&$rows) {
                    $discount = $order->diskonAwalNominal();
                    if ($discount <= 0) {
                        return;
                    }
                    $rows->push((object) [
                        'date' => $order->TglOrder,
                        'order' => $order->NoOrder,
                        'customer' => $order->customer?->NmCust ?? '-',
                        'category' => 'Diskon sebelum pembayaran',
                        'initial' => (float) $order->total,
                        'discount' => $discount,
                        'final' => (float) $order->total - $discount,
                    ]);
                });
        }

        FinalSalesDiscount::query()
            ->with('customer')
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->each(function (FinalSalesDiscount $adjustment) use (&$rows) {
                $rows->push((object) [
                    'date' => $adjustment->transaction_date,
                    'order' => $adjustment->order_number,
                    'customer' => $adjustment->customer?->NmCust ?? '-',
                    'category' => 'Potongan akhir',
                    'initial' => $adjustment->initial_amount - $adjustment->discount_before,
                    'discount' => $adjustment->discount_amount,
                    'final' => $adjustment->final_amount,
                ]);
            });

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->order)->values();

        return view('reports.sales-discounts', [
            'from' => $from, 'to' => $to, 'rows' => $rows,
            'totalDiscount' => (float) $rows->sum('discount'),
        ]);
    }
}
