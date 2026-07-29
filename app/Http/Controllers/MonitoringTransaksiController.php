<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringTransaksiController extends Controller
{
    private const GRANULARITAS_DEFAULT_DAYS = [
        'harian' => 30,
        'mingguan' => 84,   // ~12 minggu
        'bulanan' => 180,   // ~6 bulan
        'tahunan' => 1825,  // ~5 tahun
    ];

    /**
     * Combines the 3 live order tables (order_indoor/outdoor/artwork) via
     * UNION ALL, unlike Data Warehouse which reports off the legacy `notam`
     * transaction export — this is meant to answer "how's business right
     * now" rather than historical trend analysis.
     */
    public function index(Request $request): View
    {
        $granularitas = in_array($request->query('granularitas'), array_keys(self::GRANULARITAS_DEFAULT_DAYS), true)
            ? $request->query('granularitas')
            : 'bulanan';

        $status = $request->query('status') === 'lunas' ? 'lunas' : 'semua';

        $defaultDays = self::GRANULARITAS_DEFAULT_DAYS[$granularitas];
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->subDays($defaultDays)->format('Y-m-d');
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->format('Y-m-d');

        $periodExpr = match ($granularitas) {
            'harian' => "DATE_FORMAT(TglOrder, '%Y-%m-%d')",
            'mingguan' => "CONCAT(YEAR(TglOrder), '-W', LPAD(WEEK(TglOrder, 3), 2, '0'))",
            'tahunan' => "DATE_FORMAT(TglOrder, '%Y')",
            default => "DATE_FORMAT(TglOrder, '%Y-%m')",
        };

        $build = function (string $table, string $jenis) use ($periodExpr, $from, $to, $status) {
            $query = DB::table($table)
                ->selectRaw("{$periodExpr} as periode, ? as jenis, COUNT(*) as jumlah, COALESCE(SUM(total), 0) as omzet", [$jenis])
                ->whereBetween('TglOrder', [$from, $to])
                ->groupBy('periode');

            // order_indoor carries ~155k legacy rows backfilled with no
            // created_at and no computed total (see DashboardController) —
            // excluding them keeps this report scoped to orders actually
            // placed through the current pipeline, same as the Dashboard.
            if ($table === 'order_indoor') {
                $query->whereNotNull('created_at');
            }

            if ($status === 'lunas') {
                $query->where('status_bayar', 'lunas');
            }

            return $query;
        };

        $union = $build('order_indoor', 'Indoor')
            ->unionAll($build('order_outdoor', 'Outdoor'))
            ->unionAll($build('order_artwork', 'Artwork'));

        $rawRows = DB::query()->fromSub($union, 'u')->orderBy('periode')->get();

        $periods = [];
        foreach ($rawRows as $row) {
            $periods[$row->periode]['jenis'][$row->jenis] = ['jumlah' => (int) $row->jumlah, 'omzet' => (float) $row->omzet];
            $periods[$row->periode]['jumlah'] = ($periods[$row->periode]['jumlah'] ?? 0) + (int) $row->jumlah;
            $periods[$row->periode]['omzet'] = ($periods[$row->periode]['omzet'] ?? 0) + (float) $row->omzet;
        }
        ksort($periods);

        $kpi = [
            'total_transaksi' => array_sum(array_column($periods, 'jumlah')),
            'total_omzet' => array_sum(array_column($periods, 'omzet')),
        ];

        return view('monitoring-transaksi.index', [
            'granularitas' => $granularitas,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'periods' => $periods,
            'kpi' => $kpi,
        ]);
    }
}
