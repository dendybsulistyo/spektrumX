<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\HargaCetakOutdoor;
use App\Models\PrinterOutdoor;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function priceListOutdoor(): View
    {
        $printers = PrinterOutdoor::orderBy('NoUrut')->get();
        $bahans = BahanCetakOutdoor::orderBy('NoUrut')->get();
        $hargas = HargaCetakOutdoor::all()->keyBy('KdCtk');

        $tiers = [
            ['label' => '3 - 6 Hari', 'multiplier' => 1.0],
            ['label' => '7 Hari', 'multiplier' => 0.95],
            ['label' => '12 Hari', 'multiplier' => 0.90],
            ['label' => '>12 Hari', 'multiplier' => 0.85],
        ];

        return view('reports.price-list-outdoor', compact('printers', 'bahans', 'hargas', 'tiers'));
    }
}
