<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanOperatorIndoorController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'operator' => ['nullable', 'string', 'max:10'],
        ]);

        $month = CarbonImmutable::createFromFormat(
            '!Y-m',
            $filters['bulan'] ?? now()->subMonthNoOverflow()->format('Y-m'),
        );
        $operatorCode = trim($filters['operator'] ?? '');

        $operators = DB::table('operators')
            ->where('Status', 1)
            ->where('KdOpr', 'like', 'F%')
            ->orderBy('NmOpr')
            ->get(['KdOpr', 'NmOpr']);

        $notes = DB::table('notam')
            ->where('Batal', 0)
            ->whereBetween('TglNota', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderBy('NoNota')
            ->get(['NoNota']);

        $rows = $this->detailRows($notes, $operatorCode);
        $operatorNames = DB::table('operators')
            ->whereIn('KdOpr', $rows->pluck('KdOpr')->unique())
            ->pluck('NmOpr', 'KdOpr');
        $products = DB::table('produk_indoor')
            ->whereIn('KdProd', $rows->pluck('KdBrg')->unique())
            ->get(['KdProd', 'Satuan', 'isPjLb'])
            ->keyBy('KdProd');

        $report = $rows
            ->groupBy('KdOpr')
            ->map(function (Collection $operatorRows, string $code) use ($operatorNames, $products) {
                $items = $operatorRows
                    ->groupBy(fn ($row) => $row->KdBrg.'|'.$row->Produk)
                    ->map(function (Collection $productRows) use ($products) {
                        $first = $productRows->first();
                        $product = $products->get($first->KdBrg);
                        $isArea = (int) ($product->isPjLb ?? 1) === 2;

                        return (object) [
                            'code' => $first->KdBrg,
                            'product' => $first->Produk,
                            'quantity' => $productRows->sum(fn ($row) => $isArea
                                ? (float) $row->Panjang * (float) $row->Lebar * (int) $row->Qty
                                : (int) $row->Qty),
                            'unit' => $product->Satuan ?? '-',
                            'subtotal' => (float) $productRows->sum('SubItem'),
                            'discount' => (float) $productRows->sum('Disc'),
                            'total' => (float) $productRows->sum('Jumlah'),
                        ];
                    })
                    ->sortBy('code', SORT_NATURAL)
                    ->values();

                return (object) [
                    'code' => $code,
                    'name' => $operatorNames[$code] ?? $code,
                    'items' => $items,
                    'grand_total' => (float) $items->sum('total'),
                ];
            })
            ->sortBy('code', SORT_NATURAL)
            ->values();

        return view('laporan-operator-indoor.index', [
            'month' => $month,
            'operatorCode' => $operatorCode,
            'operators' => $operators,
            'report' => $report,
            'summary' => (object) [
                'operators' => $report->count(),
                'products' => $report->sum(fn ($operator) => $operator->items->count()),
                'grand_total' => (float) $report->sum('grand_total'),
            ],
        ]);
    }

    private function detailRows(Collection $notes, string $operatorCode): Collection
    {
        if ($notes->isEmpty()) {
            return collect();
        }

        $noteNumbers = $notes->pluck('NoNota')->map(fn ($number) => (string) $number)->flip();
        $first = (string) $notes->first()->NoNota;
        $last = (string) $notes->last()->NoNota;

        return DB::table('notad_transaksi_INDOOR_CEK')
            ->where('BrsNota', '>=', $first)
            ->where('BrsNota', '<', $last.'~')
            ->when($operatorCode !== '', fn ($query) => $query->where('KdOpr', $operatorCode))
            ->get(['BrsNota', 'KdOpr', 'KdBrg', 'Produk', 'Panjang', 'Lebar', 'Qty', 'SubItem', 'Disc', 'Jumlah'])
            ->filter(fn ($row) => $noteNumbers->has(substr((string) $row->BrsNota, 0, 11)))
            ->values();
    }
}
