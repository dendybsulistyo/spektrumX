<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdukRequest;
use App\Models\HargaBertingkat;
use App\Models\Kategori;
use App\Models\OrderIndoorDetail;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        $produk = Produk::query()
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

        return view('produk.index', [
            'produk' => $produk,
            'kategoriList' => Kategori::orderBy('NoUrut')->get(),
        ]);
    }

    public function create(): View
    {
        return view('produk.create', ['kategoriList' => Kategori::orderBy('NoUrut')->get()]);
    }

    public function store(StoreProdukRequest $request): RedirectResponse
    {
        Produk::create($request->validated());

        return redirect()->route('detail-indoor.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View
    {
        return view('produk.edit', ['produk' => $produk, 'kategoriList' => Kategori::orderBy('NoUrut')->get()]);
    }

    public function update(StoreProdukRequest $request, Produk $produk): RedirectResponse|JsonResponse
    {
        $oldKdProd = $produk->KdProd;

        DB::transaction(function () use ($request, $produk, $oldKdProd) {
            $produk->update($request->validated());

            if ($request->has('bertingkat')) {
                HargaBertingkat::where('KdProd', $oldKdProd)->delete();

                if ($produk->isHPilih === 1) {
                    $tiers = collect($request->input('bertingkat', []))
                        ->filter(fn ($t) => $t['BatasA'] !== null && $t['BatasA'] !== '')
                        ->map(fn ($t) => [
                            'KdProd' => $produk->KdProd,
                            'BatasA' => (int) $t['BatasA'],
                            'BatasZ' => (int) ($t['BatasZ'] ?? 0),
                            'Harga' => (float) ($t['Harga'] ?? 0),
                        ]);

                    if ($tiers->isNotEmpty()) {
                        HargaBertingkat::insert($tiers->all());
                    }
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'produk' => $produk->fresh('kategori'),
                'bertingkat' => HargaBertingkat::where('KdProd', $produk->KdProd)->orderBy('BatasA')->get(),
            ]);
        }

        return redirect()->route('produk.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        // Item SO merujuk ke KdProd (nama & hitung harga); produk yang pernah dipakai tidak boleh dihapus.
        $used = OrderIndoorDetail::query()
            ->where('KdProd', $produk->KdProd)
            ->where(fn ($q) => $q->where('jenis_produk', 'indoor')->orWhereNull('jenis_produk'))
            ->count();
        if ($used > 0) {
            return back()->with('error', "Produk {$produk->KdProd} {$produk->NmProd} sudah dipakai di {$used} item order, jadi tidak bisa dihapus. Ubah nama/harganya saja bila perlu.");
        }

        DB::transaction(function () use ($produk) {
            HargaBertingkat::where('KdProd', $produk->KdProd)->delete();
            $produk->delete();
        });

        return redirect()->route('detail-indoor.index', request()->only('search', 'page'))
            ->with('status', "Produk {$produk->KdProd} {$produk->NmProd} berhasil dihapus.");
    }
}
