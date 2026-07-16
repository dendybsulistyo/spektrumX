<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHargaCetakOutdoorRequest;
use App\Models\HargaCetakOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HargaCetakOutdoorController extends Controller
{
    public function index(Request $request): View
    {
        $hargaCetakOutdoor = HargaCetakOutdoor::query()
            ->when($request->filled('search'), fn ($q) => $q->where('KdCtk', 'like', '%'.$request->string('search').'%'))
            ->orderBy('KdCtk')
            ->paginate(20)
            ->withQueryString();

        return view('harga-cetak-outdoor.index', compact('hargaCetakOutdoor'));
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
