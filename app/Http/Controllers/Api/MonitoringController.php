<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(private readonly DashboardStatsService $stats) {}

    public function orders(Request $request): JsonResponse
    {
        abort_unless($request->user()->role?->name === 'admin', 403, 'Halaman ini khusus admin.');

        return response()->json([
            'orders' => $this->stats->recentOrders(),
        ]);
    }
}
