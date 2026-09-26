<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBahanOutdoorRequest;
use App\Models\BahanOutdoor;
use App\Models\KategoriBahanOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BahanOutdoorController extends Controller
{
    public function index(Request $request): View
    {
        $kategoriList = KategoriBahanOutdoor::query()
            ->select(['KdGrup', 'NmGrup', 'NoUrut'])
            ->orderBy('NoUrut')
            ->get();

        $bahanOutdoor = BahanOutdoor::query()
            ->select(['id', 'KdBrgs', 'KdGrup', 'NmBrgs', 'Keters', 'Satuan', 'NoUrut'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmBrgs', 'like', "%{$search}%")
                    ->orWhere('KdBrgs', 'like', "%{$search}%");
            })
            ->when($request->filled('kategori'), fn ($q) => $q->where('KdGrup', $request->string('kategori')))
            ->orderBy('NoUrut')
            ->paginate(20)
            ->withQueryString();

        $kategoriByCode = $kategoriList->keyBy('KdGrup');
        $bahanOutdoor->getCollection()->each(
            fn (BahanOutdoor $item) => $item->setRelation('kategori', $kategoriByCode->get($item->KdGrup))
        );

        return view('bahan-outdoor.index', [
            'bahanOutdoor' => $bahanOutdoor,
            'kategoriList' => $kategoriList,
        ]);
    }

    public function create(): View
    {
        return view('bahan-outdoor.create', [
            'kategoriList' => KategoriBahanOutdoor::query()
                ->select(['KdGrup', 'NmGrup', 'NoUrut'])
                ->orderBy('NoUrut')
                ->get(),
        ]);
    }

    public function store(StoreBahanOutdoorRequest $request): RedirectResponse
    {
        BahanOutdoor::create($request->validated());

        return redirect()->route('bahan-outdoor.index')->with('status', 'Bahan berhasil ditambahkan.');
    }

    public function edit(BahanOutdoor $bahanOutdoor): View
    {
        return view('bahan-outdoor.edit', [
            'bahanOutdoor' => $bahanOutdoor,
            'kategoriList' => KategoriBahanOutdoor::query()
                ->select(['KdGrup', 'NmGrup', 'NoUrut'])
                ->orderBy('NoUrut')
                ->get(),
        ]);
    }

    public function update(StoreBahanOutdoorRequest $request, BahanOutdoor $bahanOutdoor): RedirectResponse
    {
        $bahanOutdoor->update($request->validated());

        return redirect()->route('bahan-outdoor.index')->with('status', 'Bahan berhasil diperbarui.');
    }

    public function destroy(BahanOutdoor $bahanOutdoor): RedirectResponse
    {
        $bahanOutdoor->delete();

        return redirect()->route('bahan-outdoor.index')->with('status', 'Bahan berhasil dihapus.');
    }
}
