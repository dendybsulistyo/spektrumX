<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHargaCetakOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use App\Models\HargaCetakOutdoor;
use App\Models\PrinterOutdoor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HargaCetakOutdoorController extends Controller
{
    /**
     * KdCtk on harga_cetak_outdoor is composed of KdPrn (2 chars) + NoCetak (2 chars),
     * e.g. printer "01" + bahan NoCetak "01" = KdCtk "0101". This renders that
     * flat list as a Printer Outdoor x Bahan price matrix.
     */
    public function index(): View
    {
        $printers = PrinterOutdoor::orderBy('NoUrut')->get();
        $bahanList = BahanCetakOutdoor::orderBy('NoUrut')->get();
        $prices = HargaCetakOutdoor::all()->keyBy('KdCtk');

        return view('harga-cetak-outdoor.index', compact('printers', 'bahanList', 'prices'));
    }

    public function updateMatrix(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'harga' => ['required', 'array'],
            'harga.*.*.std' => ['nullable', 'numeric', 'min:0'],
            'harga.*.*.min' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['harga'] as $noCetak => $perPrinter) {
            foreach ($perPrinter as $kdPrn => $pair) {
                $kdCtk = $kdPrn.$noCetak;
                $std = $pair['std'] ?? null;
                $min = $pair['min'] ?? null;

                if (($std === null || $std === '') && ($min === null || $min === '')) {
                    HargaCetakOutdoor::where('KdCtk', $kdCtk)->delete();

                    continue;
                }

                HargaCetakOutdoor::updateOrCreate(
                    ['KdCtk' => $kdCtk],
                    ['HargaStd' => $std ?? $min, 'HargaMin' => $min ?? $std]
                );
            }
        }

        $message = 'Harga cetak outdoor berhasil disimpan.';

        // Grid ini disubmit lewat axios (lihat harga-cetak-outdoor/index.blade.php)
        // supaya operator tidak kehilangan posisi scroll di tabel yang panjang —
        // axios otomatis kirim Accept: application/json, jadi wantsJson() cukup
        // untuk membedakannya dari submit form biasa (fallback tanpa JS).
        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('harga-cetak-outdoor.index')->with('status', $message);
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
