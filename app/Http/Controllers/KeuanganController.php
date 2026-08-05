<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Customer;
use App\Models\JurnalEntry;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\PengaturanKeuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

        $payments = OrderPayment::with('user')
            ->whereBetween('created_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->get();

        $rows = $payments->groupBy(fn (OrderPayment $p) => $p->user_id)
            ->map(function ($group) {
                $masuk = (float) $group->where('jumlah', '>', 0)->sum('jumlah');
                $keluar = (float) $group->where('jumlah', '<', 0)->sum('jumlah') * -1;

                return [
                    'kasir' => $group->first()->user?->name ?? '-',
                    'jumlah_transaksi' => $group->count(),
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'net' => $masuk - $keluar,
                ];
            })
            ->sortByDesc('net')
            ->values();

        return view('keuangan.rekap-kasir', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rows' => $rows,
            'totalMasuk' => (float) $payments->where('jumlah', '>', 0)->sum('jumlah'),
            'totalKeluar' => (float) $payments->where('jumlah', '<', 0)->sum('jumlah') * -1,
            'jumlahTransaksi' => $payments->count(),
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

        // Biaya Lain (70002) is named/used as an expense account even though
        // its TipeDK happens to be 'K' in the legacy COA data — treat it by
        // its actual role (D-normal) rather than trusting that field for
        // this one account.
        $biayaLainKodes = ['70002'];

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

        return view('keuangan.laporan-ppn', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rate' => $rate,
            'rows' => $rows,
            'totalOmzet' => $rows->sum('total'),
            'totalDpp' => $rows->sum('dpp'),
            'totalPpn' => $rows->sum('ppn'),
        ]);
    }

    /**
     * CSV recap of the same PPN report — a plain accounting export (No,
     * Tanggal, No Order, Customer, DPP, PPN, Total) meant to hand to an
     * accountant or paste into a spreadsheet for SPT Masa PPN prep. This is
     * NOT a DJP e-Faktur bulk-import file — that format requires each
     * buyer's NPWP/address, which this app doesn't collect.
     */
    public function exportPpn(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        [$dari, $sampai, $rate, $rows] = $this->ppnData($request);

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
     * @return array{0: string, 1: string, 2: float, 3: \Illuminate\Support\Collection}
     */
    private function ppnData(Request $request): array
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');
        $rate = $request->filled('rate') ? (float) $request->input('rate') : PengaturanKeuangan::current()->tarif_ppn_default;

        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $model::query()
                ->with('customer')
                ->where('status_bayar', 'lunas')
                ->whereBetween('dibayar_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
                ->orderBy('dibayar_at')
                ->get()
                ->each(function ($order) use (&$rows, $type, $rate) {
                    $total = (float) $order->total;
                    $dpp = $rate > 0 ? $total / (1 + $rate / 100) : $total;
                    $ppn = $total - $dpp;

                    $rows->push([
                        'tanggal' => $order->dibayar_at,
                        'tipe' => ucfirst($type),
                        'no_order' => $order->NoOrder,
                        'customer' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-',
                        'total' => $total,
                        'dpp' => $dpp,
                        'ppn' => $ppn,
                    ]);
                });
        }

        return [$dari, $sampai, $rate, $rows->sortBy('tanggal')->values()];
    }
}
