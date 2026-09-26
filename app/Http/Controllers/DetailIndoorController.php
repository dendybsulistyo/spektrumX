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
        $kategoriList = Kategori::query()
            ->select(['KdDivs', 'NmDivs', 'NoUrut'])
            ->orderBy('NoUrut')
            ->get();

        $produk = Produk::query()
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

        $kategoriByCode = $kategoriList->keyBy('KdDivs');
        $produk->getCollection()->each(
            fn (Produk $item) => $item->setRelation('kategori', $kategoriByCode->get($item->KdDivs))
        );

        $bertingkat = HargaBertingkat::query()
            ->select(['KdProd', 'BatasA', 'BatasZ', 'Harga'])
            ->whereIn('KdProd', $produk->pluck('KdProd'))
            ->orderBy('KdProd')
            ->orderBy('BatasA')
            ->get()
            ->groupBy('KdProd');

        return view('detail-indoor.index', [
            'produk' => $produk,
            'kategoriList' => $kategoriList,
            'bertingkat' => $bertingkat,
        ]);
    }
}
