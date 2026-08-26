<?php

namespace App\Http\Controllers;

use App\Services\GunggunganHistoricalJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GunggunganHistoricalJournalController extends Controller
{
    public function __construct(private readonly GunggunganHistoricalJournalService $historical) {}

    public function index(Request $request): View
    {
        $year = min(2100, max(2000, (int) $request->input('tahun', 2026)));
        $month = min(12, max(1, (int) $request->input('bulan', 1)));

        return view('akuntansi.import-gunggungan', compact('year', 'month') + ['summary' => $this->historical->summary($year, $month)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['tahun' => ['required', 'integer', 'between:2000,2100'], 'bulan' => ['required', 'integer', 'between:1,12']]);
        $result = $this->historical->import((int) $data['tahun'], (int) $data['bulan']);

        return redirect()->route('akuntansi.import-gunggungan', $data)->with('status', "{$result['imported']} jurnal historis berhasil diposting, total ".number_format($result['total'], 0, ',', '.').'.');
    }
}
