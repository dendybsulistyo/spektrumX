<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriRequest;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategori = Kategori::withCount('produk')->orderBy('NoUrut')->paginate(20);

        return view('kategori.index', compact('kategori'));
    }

    public function create(): View
    {
        return view('kategori.create');
    }

    public function store(StoreKategoriRequest $request): RedirectResponse
    {
        Kategori::create($request->validated());

        return redirect()->route('kategori.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori): View
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(StoreKategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()->route('kategori.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->produk()->exists()) {
            return back()->with('error', 'Kategori ini masih dipakai produk, tidak bisa dihapus.');
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('status', 'Kategori berhasil dihapus.');
    }
}
