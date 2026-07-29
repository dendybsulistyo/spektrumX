<?php

namespace App\Http\Controllers;

use App\Models\OrderOutdoorCetakUnit;
use App\Models\OrderStatusNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringKinerjaController extends Controller
{
    /**
     * Order-level activity (one row per stage transition) comes from
     * order_status_notes, which is common to Indoor/Outdoor/Artwork and every
     * stage (desain/cetak/qc/kasir/pengambilan/pembatalan). Outdoor's cetak
     * stage is additionally tracked per physical unit in
     * order_outdoor_cetak_units (added alongside the per-unit cetak
     * workflow), which is a finer-grained productivity signal than the
     * single "order done" note it also produces — shown as its own section
     * rather than folded into the stage table so the two counts aren't
     * confused with each other.
     */
    public function index(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->format('Y-m-d');

        $stages = ['desain', 'cetak', 'qc', 'kasir', 'pengambilan', 'pembatalan'];

        $noteCounts = OrderStatusNote::query()
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->select('user_id', 'stage', DB::raw('count(*) as jumlah'))
            ->groupBy('user_id', 'stage')
            ->get();

        $users = User::whereIn('id', $noteCounts->pluck('user_id')->unique())->pluck('name', 'id');

        $staffRows = [];
        foreach ($noteCounts as $row) {
            $staffRows[$row->user_id]['name'] ??= $users[$row->user_id] ?? 'Tidak diketahui';
            $staffRows[$row->user_id]['counts'][$row->stage] = $row->jumlah;
        }
        uasort($staffRows, fn ($a, $b) => array_sum($b['counts']) <=> array_sum($a['counts']));

        $cetakUnitCounts = OrderOutdoorCetakUnit::query()
            ->with('cetakBy')
            ->whereBetween('cetak_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->select('cetak_by', DB::raw('count(*) as jumlah_unit'))
            ->groupBy('cetak_by')
            ->orderByDesc('jumlah_unit')
            ->get();

        return view('monitoring-kinerja.index', [
            'from' => $from,
            'to' => $to,
            'stages' => $stages,
            'staffRows' => $staffRows,
            'cetakUnitCounts' => $cetakUnitCounts,
        ]);
    }
}
