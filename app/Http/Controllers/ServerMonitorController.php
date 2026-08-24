<?php

namespace App\Http\Controllers;

use App\Services\ServerMonitoringService;
use Illuminate\View\View;

class ServerMonitorController extends Controller
{
    public function index(ServerMonitoringService $monitoring): View
    {
        return view('server-monitor.index', [
            'snapshot' => $monitoring->snapshot(),
        ]);
    }
}
