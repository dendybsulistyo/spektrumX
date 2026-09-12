<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\HargaCetakOutdoor;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\PrinterOutdoor;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricing) {}

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
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $model::query()->with('customer')->whereDate('TglOrder', $date)
                ->where('status', '!=', 'batal')->orderBy('NoOrder')->get()
                ->each(function ($order) use ($type, &$rows) {
                    $rawItems = match ($type) {
                        'indoor' => $order->detailItems(),
                        'outdoor' => $order->items()->with('hargaCetak')->get(),
                        'artwork' => $order->items,
                    };
                    $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                    $gross = (float) $items->sum('subtotal');
                    $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $discount = max(0, $gross - $net);
                    $payments = OrderPayment::forOrder($type, $order->id)->get();
                    $paid = max(0, min($net, (float) $payments->sum('jumlah')));
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
                            'customer' => $order->customer?->NmCust ?? '-',
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
            'date' => $date, 'rows' => $rows,
            'totals' => (object) collect(['subtotal', 'discount', 'total', 'cash', 'credit'])
                ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all(),
        ]);
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

        $orders = OrderOutdoor::with(['customer', 'createdBy', 'items.hargaCetak'])
            ->whereBetween('TglOrder', [$from, $to])->orderBy('TglOrder')->orderBy('NoOrder')->get();
        $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', 'outdoor')
            ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
            ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');
        $rows = collect();

        foreach ($orders as $order) {
            $items = $this->pricing->detailedLineItems('outdoor', $order, $order->items);
            if ($items->isEmpty()) {
                $items = collect([(object) ['name' => '-', 'bahan' => '-', 'printer' => '-',
                    'panjang' => 0, 'lebar' => 0, 'qty' => 0]]);
            }
            $total = $order->status === 'batal' ? 0 : ($order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total);
            $advance = $order->status === 'batal' ? 0 : max(0, min($total, (float) $order->jumlah_dibayar));
            $status = $order->status === 'batal' ? 'Batal' : match ($order->status_bayar) {
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

        return view('reports.outdoor-orders', compact('from', 'to', 'rows') + [
            'grandTotal' => (float) $orders->where('status', '!=', 'batal')->sum(fn ($order) => $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : $order->total),
            'totalAdvance' => (float) $orders->where('status', '!=', 'batal')->sum('jumlah_dibayar'),
        ]);
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
                $orders = $model::query()->where('KdCust', $customerCode)->where('status_bayar', 'lunas')
                    ->where('status', '!=', 'batal')->whereBetween('TglOrder', [$from, $to])
                    ->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
                    ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');

                foreach ($orders as $order) {
                    $rawItems = match ($type) {
                        'indoor' => $order->detailItems(),
                        'outdoor' => $order->items()->with('hargaCetak')->get(),
                        'artwork' => $order->items,
                    };
                    $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                    $gross = (float) $items->sum('subtotal');
                    $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $discount = max(0, $gross - $net);
                    $payments = OrderPayment::forOrder($type, $order->id)->get();
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
                $orders = $model::query()->where('KdCust', $customerCode)->where('status_bayar', 'hutang')
                    ->where('jumlah_piutang', '>', 0)->where('status', '!=', 'batal')
                    ->whereBetween('TglOrder', [$from, $to])->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')
                    ->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');

                foreach ($orders as $order) {
                    $rawItems = match ($type) {
                        'indoor' => $order->detailItems(),
                        'outdoor' => $order->items()->with('hargaCetak')->get(),
                        'artwork' => $order->items,
                    };
                    $items = $this->pricing->detailedLineItems($type, $order, $rawItems);
                    $gross = (float) $items->sum('subtotal');
                    $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $discount = max(0, $gross - $net);
                    $advance = max(0, (float) $order->jumlah_dibayar);
                    $credit = max(0, (float) $order->jumlah_piutang);
                    $allocated = ['discount' => 0.0, 'advance' => 0.0, 'credit' => 0.0];

                    foreach ($items->values() as $index => $item) {
                        $last = $index === $items->count() - 1;
                        $ratio = $gross > 0 ? (float) $item->subtotal / $gross : ($last ? 1 : 0);
                        $part = [];
                        foreach (['discount' => $discount, 'advance' => $advance, 'credit' => $credit] as $key => $amount) {
                            $part[$key] = $last ? $amount - $allocated[$key] : round($amount * $ratio);
                            $allocated[$key] += $part[$key];
                        }
                        $rows->push((object) [
                            'date' => $order->TglOrder, 'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                            'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: $item->name,
                            'description' => $item->name, 'length' => $item->panjang, 'width' => $item->lebar,
                            'qty' => $item->qty, 'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                            'discount' => $part['discount'], 'total' => (float) $item->subtotal - $part['discount'],
                            'advance' => $part['advance'], 'payment' => 0, 'credit' => $part['credit'],
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
            $orders = $model::query()->with(['customer', 'createdBy'])
                ->whereBetween('TglOrder', [$from, $to])
                ->where('status', '!=', 'batal')
                ->whereNull('invoice_voided_at')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('order_documents')
                    ->where('kind', 'inv')->where('order_type', $type)
                    ->whereColumn('order_id', $table.'.id'))
                ->orderBy('TglOrder')->orderBy('NoOrder')->get();

            foreach ($orders as $order) {
                $rawItems = match ($type) {
                    'indoor' => $order->detailItems(),
                    'outdoor' => $order->items()->with('hargaCetak')->get(),
                    'artwork' => $order->items,
                };
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
                    $discount = $order->diskonNominal();
                    if ($discount <= 0) {
                        return;
                    }
                    $rows->push((object) [
                        'date' => $order->TglOrder,
                        'order' => $order->NoOrder,
                        'customer' => $order->customer?->NmCust ?? '-',
                        'discount' => $discount,
                    ]);
                });
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->order)->values();

        return view('reports.sales-discounts', [
            'from' => $from, 'to' => $to, 'rows' => $rows,
            'totalDiscount' => (float) $rows->sum('discount'),
        ]);
    }
}
