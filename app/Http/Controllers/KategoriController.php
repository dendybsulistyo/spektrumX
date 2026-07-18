<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriProdukRequest;
use App\Http\Requests\StoreKategoriRequest;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategori = Kategori::withCount('produk')
            ->with(['produk' => fn ($q) => $q->orderBy('NoUrut')])
            ->orderBy('NoUrut')
            ->paginate(20);

        return view('kategori.index', compact('kategori'));
    }

    public function create(): View
    {
        return view('kategori.create', [
            'kategoriList' => Kategori::orderBy('NoUrut')->get(),
        ]);
    }

    /**
     * Kategori (Divisi) and Produk are created together here — a Divisi with
     * no products doesn't mean anything in the "Harga Indoor" price list, so
     * the form always collects both instead of leaving the operator to
     * create a category, then separately remember to go create a product
     * under it via a different menu.
     */
    public function store(StoreKategoriProdukRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            if ($data['kategori_mode'] === 'new') {
                Kategori::create([
                    'KdDivs' => $data['new_KdDivs'],
                    'NmDivs' => $data['new_NmDivs'],
                    'NoUrut' => $data['KategoriNoUrut'],
                ]);
                $kdDivs = $data['new_KdDivs'];
            } else {
                $kdDivs = $data['KdDivs'];
            }

            Produk::create([
                'KdProd' => $data['KdProd'],
                'KdDivs' => $kdDivs,
                'NmProd' => $data['NmProd'],
                'NoUrut' => $data['NoUrut'],
                'HargaStd' => $data['HargaStd'],
                'HargaMin' => $data['HargaMin'],
                'Satuan' => $data['Satuan'],
                'isPjLb' => $data['isPjLb'],
                'isHPilih' => $data['isHPilih'],
            ]);
        });

        return redirect()->route('kategori.index')->with('status', 'Kategori & produk berhasil ditambahkan.');
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
