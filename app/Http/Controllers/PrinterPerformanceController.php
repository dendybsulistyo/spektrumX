<?php

namespace App\Http\Controllers;

use App\Models\OrderOutdoor;
use App\Models\PrinterOutdoor;
use App\Services\OrderPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PrinterPerformanceController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricing) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'printer' => ['nullable', 'string', 'max:10'],
        ]);
        $from = $filters['from'] ?? now()->startOfMonth()->format('Y-m-d');
        $to = $filters['to'] ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $printerCode = trim($filters['printer'] ?? '');
        $printers = PrinterOutdoor::query()->orderBy('NoUrut')->orderBy('NmPrn')->get(['KdPrn', 'NmPrn']);
        $printerNames = $printers->pluck('NmPrn', 'KdPrn');

        $orders = OrderOutdoor::query()
            ->with(['items.hargaCetak', 'customer', 'cetakBy'])
            ->whereBetween('cetak_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->where('status', '!=', 'batal')
            ->whereNull('invoice_voided_at')
            ->when($printerCode !== '', fn ($query) => $query->whereHas(
                'items', fn ($items) => $items->where('KdCtk', 'like', $printerCode.'%')
            ))
            ->orderBy('cetak_at')->get();

        $printerStats = collect();
        $details = collect();

        foreach ($orders as $order) {
            $lines = $this->pricing->detailedLineItems('outdoor', $order, $order->items);
            $grossOrder = max(0, (float) $lines->sum('subtotal'));
            $netOrder = max(0, (float) $order->totalSetelahDiskon());
            $netRatio = $grossOrder > 0 ? min(1, $netOrder / $grossOrder) : 0;

            foreach ($order->items as $index => $item) {
                $code = $item->printerCode();
                if (! $code || ($printerCode !== '' && $code !== $printerCode)) {
                    continue;
                }

                $line = $lines->get($index);
                $grossRevenue = (float) ($line?->subtotal ?? 0);
                $netRevenue = round($grossRevenue * $netRatio);
                $qty = (int) $item->Qty;
                $area = ((float) $item->Panjang / 100) * ((float) $item->Lebar / 100) * $qty;
                $operatorId = $order->cetak_by ? (string) $order->cetak_by : 'unknown';
                $operatorName = $order->cetakBy?->name ?? 'Belum tercatat';
                $stats = $printerStats->get($code, [
                    'code' => $code,
                    'name' => $printerNames[$code] ?? $code,
                    'order_ids' => [],
                    'items' => 0,
                    'qty' => 0,
                    'area' => 0.0,
                    'gross_revenue' => 0.0,
                    'net_revenue' => 0.0,
                    'operators' => [],
                ]);
                $stats['order_ids'][$order->id] = true;
                $stats['items']++;
                $stats['qty'] += $qty;
                $stats['area'] += $area;
                $stats['gross_revenue'] += $grossRevenue;
                $stats['net_revenue'] += $netRevenue;
                $stats['operators'][$operatorId] ??= [
                    'name' => $operatorName, 'order_ids' => [], 'items' => 0, 'qty' => 0, 'area' => 0.0, 'revenue' => 0.0,
                ];
                $stats['operators'][$operatorId]['order_ids'][$order->id] = true;
                $stats['operators'][$operatorId]['items']++;
                $stats['operators'][$operatorId]['qty'] += $qty;
                $stats['operators'][$operatorId]['area'] += $area;
                $stats['operators'][$operatorId]['revenue'] += $netRevenue;
                $printerStats->put($code, $stats);

                $details->push((object) [
                    'printed_at' => $order->cetak_at,
                    'order_number' => $order->NoOrder,
                    'customer' => $order->customer?->NmCust ?? '-',
                    'printer' => $printerNames[$code] ?? $code,
                    'operator' => $operatorName,
                    'item' => $item->NmFile ?: '-',
                    'qty' => $qty,
                    'area' => $area,
                    'gross_revenue' => $grossRevenue,
                    'net_revenue' => $netRevenue,
                ]);
            }
        }

        $rows = $printerStats->map(function (array $stats) {
            $operators = collect($stats['operators'])->map(function (array $operator) {
                $operator['orders'] = count($operator['order_ids']);
                unset($operator['order_ids']);

                return (object) $operator;
            })->sortByDesc('items')->values();

            return (object) [
                'code' => $stats['code'],
                'name' => $stats['name'],
                'orders' => count($stats['order_ids']),
                'items' => $stats['items'],
                'qty' => $stats['qty'],
                'area' => $stats['area'],
                'gross_revenue' => $stats['gross_revenue'],
                'net_revenue' => $stats['net_revenue'],
                'operators' => $operators,
                'top_operator' => $operators->first(),
            ];
        })->sortByDesc('net_revenue')->values();

        $summary = (object) [
            'printers' => $rows->count(),
            'orders' => $details->pluck('order_number')->unique()->count(),
            'items' => $details->count(),
            'qty' => (int) $details->sum('qty'),
            'area' => (float) $details->sum('area'),
            'gross_revenue' => (float) $details->sum('gross_revenue'),
            'net_revenue' => (float) $details->sum('net_revenue'),
        ];

        return view('printer-performance.index', [
            'from' => Carbon::parse($from)->format('Y-m-d'),
            'to' => Carbon::parse($to)->format('Y-m-d'),
            'printerCode' => $printerCode,
            'printers' => $printers,
            'rows' => $rows,
            'details' => $details->sortByDesc('printed_at')->values(),
            'summary' => $summary,
        ]);
    }
}
