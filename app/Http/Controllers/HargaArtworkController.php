<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHargaArtworkRequest;
use App\Models\HargaArtwork;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HargaArtworkController extends Controller
{
    public function index(Request $request): View
    {
        $hargaArtwork = HargaArtwork::query()
            ->with('kategori')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmProd', 'like', "%{$search}%")
                    ->orWhere('KdProd', 'like', "%{$search}%");
            })
            ->when($request->filled('kategori'), fn ($q) => $q->where('KdDivs', $request->string('kategori')))
            ->orderBy('NoUrut')
            ->paginate(15)
            ->withQueryString();

        return view('harga-artwork.index', [
            'hargaArtwork' => $hargaArtwork,
            'kategoriList' => Kategori::orderBy('NoUrut')->get(),
        ]);
    }

    public function create(): View
    {
        return view('harga-artwork.create', ['kategoriList' => Kategori::orderBy('NoUrut')->get()]);
    }

    public function store(StoreHargaArtworkRequest $request): RedirectResponse
    {
        HargaArtwork::create($request->validated());

        return redirect()->route('harga-artwork.index')->with('status', 'Harga artwork berhasil ditambahkan.');
    }

    public function edit(HargaArtwork $hargaArtwork): View
    {
        return view('harga-artwork.edit', ['hargaArtwork' => $hargaArtwork, 'kategoriList' => Kategori::orderBy('NoUrut')->get()]);
    }

    public function update(StoreHargaArtworkRequest $request, HargaArtwork $hargaArtwork): RedirectResponse
    {
        $hargaArtwork->update($request->validated());

        return redirect()->route('harga-artwork.index')->with('status', 'Harga artwork berhasil diperbarui.');
    }

    public function destroy(HargaArtwork $hargaArtwork): RedirectResponse
    {
        $hargaArtwork->delete();

        return redirect()->route('harga-artwork.index')->with('status', 'Harga artwork berhasil dihapus.');
    }
}
