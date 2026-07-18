<?php

namespace App\Http\Controllers;

use App\Models\HargaBertingkat;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DetailIndoorController extends Controller
{
    public function index(Request $request): View
    {
        $produk = Produk::query()
            ->with('kategori')
            ->join('kategori_produk_indoor', 'kategori_produk_indoor.KdDivs', '=', 'produk_indoor.KdDivs')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmProd', 'like', "%{$search}%")
                    ->orWhere('produk_indoor.KdProd', 'like', "%{$search}%");
            })
            ->orderBy('kategori_produk_indoor.NoUrut')
            ->orderBy('produk_indoor.NoUrut')
            ->select('produk_indoor.*')
            ->paginate(30)
            ->withQueryString();

        $bertingkat = HargaBertingkat::query()
            ->whereIn('KdProd', $produk->pluck('KdProd'))
            ->orderBy('KdProd')
            ->orderBy('BatasA')
            ->get()
            ->groupBy('KdProd');

        return view('detail-indoor.index', [
            'produk' => $produk,
            'kategoriList' => Kategori::orderBy('NoUrut')->get(),
            'bertingkat' => $bertingkat,
        ]);
    }
}
