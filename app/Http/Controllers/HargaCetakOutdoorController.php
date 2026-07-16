<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHargaCetakOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use App\Models\HargaCetakOutdoor;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HargaCetakOutdoorController extends Controller
{
    /**
     * KdCtk on hcetak_outdoor is composed of KdPrn (2 chars) + NoCetak (2 chars),
     * e.g. printer "01" + bahan NoCetak "01" = KdCtk "0101". This renders that
     * flat list as a Printer x Bahan price matrix.
     */
    public function index(): View
    {
        $printers = Printer::orderBy('NoUrut')->get();
        $bahanList = BahanCetakOutdoor::orderBy('NoUrut')->get();
        $prices = HargaCetakOutdoor::all()->keyBy('KdCtk');

        return view('harga-cetak-outdoor.index', compact('printers', 'bahanList', 'prices'));
    }

    public function updateMatrix(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'harga' => ['required', 'array'],
            'harga.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['harga'] as $noCetak => $perPrinter) {
            foreach ($perPrinter as $kdPrn => $harga) {
                $kdCtk = $kdPrn.$noCetak;

                if ($harga === null || $harga === '') {
                    HargaCetakOutdoor::where('KdCtk', $kdCtk)->delete();

                    continue;
                }

                HargaCetakOutdoor::updateOrCreate(
                    ['KdCtk' => $kdCtk],
                    ['HargaStd' => $harga, 'HargaMin' => $harga]
                );
            }
        }

        return redirect()->route('harga-cetak-outdoor.index')->with('status', 'Harga cetak outdoor berhasil disimpan.');
    }

    public function create(): View
    {
        return view('harga-cetak-outdoor.create');
    }

    public function store(StoreHargaCetakOutdoorRequest $request): RedirectResponse
    {
        HargaCetakOutdoor::create($request->validated());

        return redirect()->route('harga-cetak-outdoor.index')->with('status', 'Harga cetak berhasil ditambahkan.');
    }

    public function edit(HargaCetakOutdoor $hargaCetakOutdoor): View
    {
        return view('harga-cetak-outdoor.edit', compact('hargaCetakOutdoor'));
    }

    public function update(StoreHargaCetakOutdoorRequest $request, HargaCetakOutdoor $hargaCetakOutdoor): RedirectResponse
    {
        $hargaCetakOutdoor->update($request->validated());

        return redirect()->route('harga-cetak-outdoor.index')->with('status', 'Harga cetak berhasil diperbarui.');
    }

    public function destroy(HargaCetakOutdoor $hargaCetakOutdoor): RedirectResponse
    {
        $hargaCetakOutdoor->delete();

        return redirect()->route('harga-cetak-outdoor.index')->with('status', 'Harga cetak berhasil dihapus.');
    }
}
