<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataWarehouseController extends Controller
{
    /**
     * Sourced from the nota tables (notam / notad_transaksi_INDOOR_CEK),
     * refreshed 2026-07-18 from the source system's live export — this now
     * carries transaction history through the present day (~220k invoices /
     * 517k line items), not just the 2019-2024 legacy backfill. Product/
     * customer breakdowns come straight from notad_transaksi_INDOOR_CEK's
     * own Qty/Jumlah columns rather than being recomputed from current
     * produk_indoor pricing, so historical prices are reflected accurately
     * even where they've since changed.
     *
     * notad_transaksi_INDOOR_CEK has no date or Batal(cancelled) column of
     * its own — joining it to notam by BrsNota prefix has no usable index
     * (MySQL falls back to a block-nested-loop scan and times out), so
     * product/customer-item breakdowns are all-time and don't exclude the
     * small share of cancelled transactions. KPIs/trend/top customers come
     * from notam directly (indexed, fast) and do respect the date filter
     * and Batal flag.
     */
    public function index(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : null;
        $to = $request->filled('to') ? $request->string('to')->toString() : null;

        $notam = DB::table('notam')->where('Batal', 0);
        if ($from) {
            $notam->where('TglNota', '>=', $from);
        }
        if ($to) {
            $notam->where('TglNota', '<=', $to);
        }

        $kpi = (clone $notam)->selectRaw('
            COUNT(*) as total_transaksi,
            COALESCE(SUM(Total), 0) as total_omzet,
            COUNT(DISTINCT KdCust) as total_customer
        ')->first();

        $trend = (clone $notam)
            ->selectRaw("DATE_FORMAT(TglNota, '%Y-%m') as bulan, SUM(Total) as omzet, COUNT(*) as jumlah")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $topCustomers = (clone $notam)
            ->select('KdCust', DB::raw('SUM(Total) as total_belanja'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy('KdCust')
            ->orderByDesc('total_belanja')
            ->limit(10)
            ->get();

        $custNames = DB::table('customers')
            ->whereIn('KdCust', $topCustomers->pluck('KdCust'))
            ->pluck('NmCust', 'KdCust');

        $topCustomers = $topCustomers->map(fn ($c) => (object) [
            'KdCust' => $c->KdCust,
            'NmCust' => $custNames[$c->KdCust] ?? $c->KdCust,
            'total_belanja' => $c->total_belanja,
            'jumlah_transaksi' => $c->jumlah_transaksi,
        ]);

        $topProdukQty = DB::table('notad_transaksi_INDOOR_CEK')
            ->select('Produk', DB::raw('SUM(Qty) as total_qty'), DB::raw('SUM(Jumlah) as total_omzet'))
            ->groupBy('Produk')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $topProdukOmzet = DB::table('notad_transaksi_INDOOR_CEK')
            ->select('Produk', DB::raw('SUM(Qty) as total_qty'), DB::raw('SUM(Jumlah) as total_omzet'))
            ->groupBy('Produk')
            ->orderByDesc('total_omzet')
            ->limit(10)
            ->get();

        $bottomProduk = DB::table('notad_transaksi_INDOOR_CEK')
            ->select('Produk', DB::raw('SUM(Qty) as total_qty'), DB::raw('SUM(Jumlah) as total_omzet'))
            ->groupBy('Produk')
            ->havingRaw('SUM(Qty) > 0')
            ->orderBy('total_qty')
            ->limit(10)
            ->get();

        $statusPembayaran = (clone $notam)
            ->select('Tunai', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(Total) as omzet'))
            ->groupBy('Tunai')
            ->get();

        [$activitySummary, $vipCustomers, $activityWindow] = $this->customerActivity();

        $custSearch = trim((string) $request->query('cust_search', ''));
        $custStatus = (string) $request->query('cust_status', 'semua');

        $nonVipCustomers = DB::table('customers as c')
            ->leftJoin('customer_limits as cl', 'cl.KdCust', '=', 'c.KdCust')
            ->leftJoinSub(
                DB::table('notam')->select('KdCust')->where('Batal', 0)
                    ->whereBetween('TglNota', [$activityWindow['start'], $activityWindow['end']])
                    ->distinct(),
                'active', 'active.KdCust', '=', 'c.KdCust',
            )
            ->whereNull('cl.KdCust')
            ->whereRaw("TRIM(c.KdCust) != ''")
            ->when($custSearch !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('c.NmCust', 'like', "%{$custSearch}%")
                ->orWhere('c.KdCust', 'like', "%{$custSearch}%")))
            ->when($custStatus === 'aktif', fn ($q) => $q->whereNotNull('active.KdCust'))
            ->when($custStatus === 'tidak_aktif', fn ($q) => $q->whereNull('active.KdCust'))
            ->select('c.KdCust', 'c.NmCust', DB::raw('CASE WHEN active.KdCust IS NOT NULL THEN 1 ELSE 0 END as aktif'))
            ->orderBy('c.NmCust')
            ->paginate(20, ['*'], 'cust_page')
            ->withQueryString();

        return view('data-warehouse.index', [
            'from' => $from,
            'to' => $to,
            'kpi' => $kpi,
            'trend' => $trend,
            'topCustomers' => $topCustomers,
            'topProdukQty' => $topProdukQty,
            'topProdukOmzet' => $topProdukOmzet,
            'bottomProduk' => $bottomProduk,
            'statusPembayaran' => $statusPembayaran,
            'activitySummary' => $activitySummary,
            'vipCustomers' => $vipCustomers,
            'activityWindow' => $activityWindow,
            'nonVipCustomers' => $nonVipCustomers,
            'custSearch' => $custSearch,
            'custStatus' => $custStatus,
            'dataRange' => [
                'min' => DB::table('notam')->min('TglNota'),
                'max' => DB::table('notam')->max('TglNota'),
            ],
        ]);
    }

    /**
     * "Aktif" = at least one non-cancelled transaction between 1 Jan of
     * (current year - 1) and today. notam now carries data through the
     * present (refreshed from the source system's live export), so this
     * anchors on the real current date again.
     *
     * @return array{0: object, 1: \Illuminate\Support\Collection, 2: array{start: string, end: string}}
     */
    private function customerActivity(): array
    {
        $refDate = now()->toDateString();
        $refYear = (int) date('Y');
        $windowStart = ($refYear - 1).'-01-01';

        $summaryRows = DB::select("
            SELECT
                CASE WHEN cl.KdCust IS NOT NULL THEN 'vip' ELSE 'non_vip' END AS segment,
                CASE WHEN active.KdCust IS NOT NULL THEN 'aktif' ELSE 'tidak_aktif' END AS status,
                COUNT(*) AS jumlah
            FROM customers c
            LEFT JOIN customer_limits cl ON cl.KdCust = c.KdCust
            LEFT JOIN (
                SELECT DISTINCT KdCust FROM notam WHERE Batal = 0 AND TglNota BETWEEN ? AND ?
            ) active ON active.KdCust = c.KdCust
            WHERE TRIM(c.KdCust) != ''
            GROUP BY segment, status
        ", [$windowStart, $refDate]);

        $summary = (object) [
            'non_vip_aktif' => 0, 'non_vip_tidak_aktif' => 0,
            'vip_aktif' => 0, 'vip_tidak_aktif' => 0,
        ];
        foreach ($summaryRows as $row) {
            $summary->{"{$row->segment}_{$row->status}"} = (int) $row->jumlah;
        }

        $vipCustomers = collect(DB::select("
            SELECT
                c.KdCust, c.NmCust,
                CASE WHEN active.KdCust IS NOT NULL THEN 1 ELSE 0 END AS aktif
            FROM customers c
            JOIN customer_limits cl ON cl.KdCust = c.KdCust
            LEFT JOIN (
                SELECT DISTINCT KdCust FROM notam WHERE Batal = 0 AND TglNota BETWEEN ? AND ?
            ) active ON active.KdCust = c.KdCust
            ORDER BY aktif DESC, c.NmCust
        ", [$windowStart, $refDate]));

        return [$summary, $vipCustomers, ['start' => $windowStart, 'end' => $refDate]];
    }
}
