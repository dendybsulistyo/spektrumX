<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdukRequest;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmProd', 'like', "%{$search}%")
                    ->orWhere('KdProd', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(15)
            ->withQueryString();

        return view('produk.index', compact('produk'));
    }

    public function create(): View
    {
        return view('produk.create');
    }

    public function store(StoreProdukRequest $request): RedirectResponse
    {
        Produk::create($request->validated());

        return redirect()->route('produk.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View
    {
        return view('produk.edit', compact('produk'));
    }

    public function update(StoreProdukRequest $request, Produk $produk): RedirectResponse
    {
        $produk->update($request->validated());

        return redirect()->route('produk.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $produk->delete();

        return redirect()->route('produk.index')->with('status', 'Produk berhasil dihapus.');
    }
}
