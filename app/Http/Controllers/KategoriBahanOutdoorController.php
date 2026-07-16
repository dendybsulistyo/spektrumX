<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriBahanOutdoorRequest;
use App\Models\KategoriBahanOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriBahanOutdoorController extends Controller
{
    public function index(Request $request): View
    {
        $kategoriBahanOutdoor = KategoriBahanOutdoor::withCount('bahan')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmGrup', 'like', "%{$search}%")
                    ->orWhere('KdGrup', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(20)
            ->withQueryString();

        return view('kategori-bahan-outdoor.index', compact('kategoriBahanOutdoor'));
    }

    public function create(): View
    {
        return view('kategori-bahan-outdoor.create');
    }

    public function store(StoreKategoriBahanOutdoorRequest $request): RedirectResponse
    {
        KategoriBahanOutdoor::create($request->validated());

        return redirect()->route('kategori-bahan-outdoor.index')->with('status', 'Kategori bahan berhasil ditambahkan.');
    }

    public function edit(KategoriBahanOutdoor $kategoriBahanOutdoor): View
    {
        return view('kategori-bahan-outdoor.edit', compact('kategoriBahanOutdoor'));
    }

    public function update(StoreKategoriBahanOutdoorRequest $request, KategoriBahanOutdoor $kategoriBahanOutdoor): RedirectResponse
    {
        $kategoriBahanOutdoor->update($request->validated());

        return redirect()->route('kategori-bahan-outdoor.index')->with('status', 'Kategori bahan berhasil diperbarui.');
    }

    public function destroy(KategoriBahanOutdoor $kategoriBahanOutdoor): RedirectResponse
    {
        if ($kategoriBahanOutdoor->bahan()->exists()) {
            return back()->with('error', 'Kategori ini masih dipakai bahan, tidak bisa dihapus.');
        }

        $kategoriBahanOutdoor->delete();

        return redirect()->route('kategori-bahan-outdoor.index')->with('status', 'Kategori bahan berhasil dihapus.');
    }
}
