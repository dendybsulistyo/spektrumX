<?php

namespace App\Http\Controllers;

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
     * stage (desain/cetak/qc/kasir/pengambilan/pembatalan).
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

        return view('monitoring-kinerja.index', [
            'from' => $from,
            'to' => $to,
            'stages' => $stages,
            'staffRows' => $staffRows,
        ]);
    }
}
