<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBahanCetakOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BahanCetakOutdoorController extends Controller
{
    public function index(Request $request): View
    {
        $bahanCetakOutdoors = BahanCetakOutdoor::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmBhn', 'like', "%{$search}%")
                    ->orWhere('NoCetak', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(15)
            ->withQueryString();

        return view('bahan-cetak-outdoor.index', compact('bahanCetakOutdoors'));
    }

    public function create(): View
    {
        return view('bahan-cetak-outdoor.create');
    }

    public function store(StoreBahanCetakOutdoorRequest $request): RedirectResponse
    {
        BahanCetakOutdoor::create($request->validated());

        return redirect()->route('bahan-cetak-outdoor.index')->with('status', 'Bahan berhasil ditambahkan.');
    }

    public function edit(BahanCetakOutdoor $bahanCetakOutdoor): View
    {
        return view('bahan-cetak-outdoor.edit', compact('bahanCetakOutdoor'));
    }

    public function update(StoreBahanCetakOutdoorRequest $request, BahanCetakOutdoor $bahanCetakOutdoor): RedirectResponse
    {
        $bahanCetakOutdoor->update($request->validated());

        return redirect()->route('bahan-cetak-outdoor.index')->with('status', 'Bahan berhasil diperbarui.');
    }

    public function destroy(BahanCetakOutdoor $bahanCetakOutdoor): RedirectResponse
    {
        $bahanCetakOutdoor->delete();

        return redirect()->route('bahan-cetak-outdoor.index')->with('status', 'Bahan berhasil dihapus.');
    }
}
