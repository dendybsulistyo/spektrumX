<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriProdukIndoorRequest;
use App\Models\KategoriProdukIndoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriProdukIndoorController extends Controller
{
    public function index(Request $request): View
    {
        $kategoriProdukIndoors = KategoriProdukIndoor::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmDivs', 'like', "%{$search}%")
                    ->orWhere('KdDivs', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(20)
            ->withQueryString();

        return view('kategori-produk-indoor.index', compact('kategoriProdukIndoors'));
    }

    public function create(): View
    {
        return view('kategori-produk-indoor.create');
    }

    public function store(StoreKategoriProdukIndoorRequest $request): RedirectResponse
    {
        KategoriProdukIndoor::create($request->validated());

        return redirect()->route('kategori-produk-indoor.index')->with('status', 'Divisi berhasil ditambahkan.');
    }

    public function edit(KategoriProdukIndoor $kategoriProdukIndoor): View
    {
        return view('kategori-produk-indoor.edit', compact('kategoriProdukIndoor'));
    }

    public function update(StoreKategoriProdukIndoorRequest $request, KategoriProdukIndoor $kategoriProdukIndoor): RedirectResponse
    {
        $kategoriProdukIndoor->update($request->validated());

        return redirect()->route('kategori-produk-indoor.index')->with('status', 'Divisi berhasil diperbarui.');
    }

    public function destroy(KategoriProdukIndoor $kategoriProdukIndoor): RedirectResponse
    {
        $kategoriProdukIndoor->delete();

        return redirect()->route('kategori-produk-indoor.index')->with('status', 'Divisi berhasil dihapus.');
    }
}
