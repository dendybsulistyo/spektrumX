<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataWarehouseController extends Controller
{
    /**
     * Sourced from the legacy nota tables (notam / notad_transaksi_INDOOR_CEK)
     * migrated from the old system — this is the richest transaction history
     * available (2019-12 to 2024-02, ~149k invoices / 360k line items),
     * far more complete than the new order_indoor tables which only have a
     * handful of rows so far. Product/customer breakdowns come straight from
     * notad_transaksi_INDOOR_CEK's own Qty/Jumlah columns rather than being
     * recomputed from current produk_indoor pricing, so historical prices are
     * reflected accurately even where they've since changed.
     *
     * notad_transaksi_INDOOR_CEK has no date or Batal(cancelled) column of
     * its own — joining it to notam by BrsNota prefix has no usable index
     * (MySQL falls back to a block-nested-loop scan across ~148k x 357k rows
     * and times out), so product/customer-item breakdowns are all-time and
     * don't exclude the ~0.8% cancelled transactions. KPIs/trend/top
     * customers come from notam directly (indexed, fast) and do respect the
     * date filter and Batal flag.
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
            'dataRange' => [
                'min' => DB::table('notam')->min('TglNota'),
                'max' => DB::table('notam')->max('TglNota'),
            ],
        ]);
    }
}
