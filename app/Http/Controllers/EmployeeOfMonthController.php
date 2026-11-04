<?php

namespace App\Http\Controllers;

use App\Services\EmployeeOfMonthService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Employee of the Month per divisi (versi santai, 100% data sistem). */
class EmployeeOfMonthController extends Controller
{
    public function __invoke(Request $request, EmployeeOfMonthService $service): View
    {
        $filters = $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $month = isset($filters['bulan']) ? Carbon::createFromFormat('Y-m-d', $filters['bulan'].'-01') : now();

        return view('employee-of-month.index', $service->compute($month) + [
            'bulan' => $month->format('Y-m'),
            'minDaysDefault' => EmployeeOfMonthService::MIN_ACTIVE_DAYS,
        ]);
    }
}
