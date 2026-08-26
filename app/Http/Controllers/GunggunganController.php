<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GunggunganController extends Controller
{
    public function index(Request $request): View
    {
        [$year, $month] = $this->period($request); $days = Carbon::create($year, $month, 1)->daysInMonth;
        $rawDay = $request->string('tanggal')->toString(); $day = ctype_digit($rawDay) ? (int) $rawDay : 0;
        $base = $this->transactions($year, $month);
        $daily = (clone $base)->select('TglOrder', DB::raw('COUNT(*) jumlah_nota'), DB::raw('SUM(total) total'))->groupBy('TglOrder')->orderBy('TglOrder')->get()->keyBy('TglOrder');
        $first = Carbon::create($year, $month, 1); $start = $first->copy()->startOfWeek(Carbon::SUNDAY); $end = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $calendar = collect(range(0, $start->diffInDays($end)))->map(function (int $offset) use ($start, $year, $month, $daily) {
            $date = $start->copy()->addDays($offset); $current = $date->year === $year && $date->month === $month; $row = $current ? $daily->get($date->toDateString()) : null;
            return (object) ['date' => $date->toDateString(), 'day' => $date->day, 'isCurrent' => $current, 'jumlah_nota' => (int) ($row->jumlah_nota ?? 0), 'total' => (float) ($row->total ?? 0)];
        });
        $date = $day >= 1 && $day <= $days ? sprintf('%04d-%02d-%02d', $year, $month, $day) : '';
        if ($date) $base->where('TglOrder', $date);
        $orders = $base->orderByDesc('TglOrder')->orderBy('NoOrder')->paginate(50)->withQueryString();
        $customers = DB::table('customers')
            ->whereIn('KdCust', $orders->pluck('KdCust')->filter()->unique())
            ->pluck('NmCust', 'KdCust');

        $orders->getCollection()->each(function ($order) use ($customers) {
            $order->customer = (object) ['NmCust' => $customers->get($order->KdCust, '-')];
            $order->dpp = round($order->total / 1.11);
            $order->dpp_nilai_lain = round($order->dpp * 11 / 12);
            $order->ppn = $order->total - $order->dpp;
        });
        return view('akuntansi.gunggungan', ['orders' => $orders, 'calendar' => $calendar, 'totalNota' => (int) $daily->sum('jumlah_nota'), 'totalNilai' => (float) $daily->sum('total'), 'selectedDate' => $date, 'day' => $day, 'year' => $year, 'month' => $month]);
    }

    public function rekapOmset(Request $request): View
    {
        [$year, $month] = $this->period($request); $rows = $this->transactions($year, $month)->select('TglOrder', DB::raw('SUM(total) total'))->groupBy('TglOrder')->pluck('total', 'TglOrder');
        $daily = collect(range(1, Carbon::create($year, $month, 1)->daysInMonth))->map(function (int $day) use ($year, $month, $rows) {$total = (float) ($rows[sprintf('%04d-%02d-%02d', $year, $month, $day)] ?? 0); $dpp = $total ? round($total / 1.11) : 0; return (object) ['day' => $day, 'total' => $total, 'dpp' => $dpp, 'ppn' => $total - $dpp];});
        return view('akuntansi.rekap-omset', compact('year', 'month', 'daily') + ['totals' => (object) ['dpp' => $daily->sum('dpp'), 'ppn' => $daily->sum('ppn'), 'total' => $daily->sum('total')]]);
    }

    private function period(Request $request): array { return [(int) $request->input('tahun', now()->year), min(12, max(1, (int) $request->input('bulan', now()->month)))]; }

    private function transactions(int $year, int $month): Builder
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $paidStatuses = ['lunas', 'dp', 'hutang'];
        $indoor = DB::table('order_indoor')
            ->selectRaw("'indoor' jenis,id,TglOrder,NoOrder,KdCust,total,status_bayar")
            ->whereBetween('TglOrder', [$start, $end])
            ->whereIn('status_bayar', $paidStatuses);
        $outdoor = DB::table('order_outdoor')
            ->selectRaw("'outdoor' jenis,id,TglOrder,NoOrder,KdCust,total,status_bayar")
            ->whereBetween('TglOrder', [$start, $end])
            ->whereIn('status_bayar', $paidStatuses);

        return DB::query()
            ->fromSub($indoor->unionAll($outdoor), 'orders')
            ->select('orders.*')
            ->whereNotNull('orders.total');
    }
}
