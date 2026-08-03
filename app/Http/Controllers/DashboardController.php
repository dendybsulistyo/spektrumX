<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatsService $stats) {}

    public function index(Request $request): View
    {
        return view('dashboard', $this->loadData($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(Request $request): array
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : null;
        $to = $request->filled('to') ? $request->string('to')->toString() : null;

        // Tanpa filter tanggal, cukup tampilkan 30 order terbaru seperti
        // biasa. Begitu operator mencari rentang tanggal tertentu, batas
        // dinaikkan supaya semua order dalam rentang itu ikut tampil.
        $limit = ($from || $to) ? 500 : 30;

        return [
            'stats' => $this->stats->stats($from, $to),
            'recent' => $this->stats->recentOrders($limit, $from, $to),
            'from' => $from,
            'to' => $to,
        ];
    }
}
