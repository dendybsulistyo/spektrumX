<?php

namespace App\Http\Controllers;

use App\Models\AccountingFixedAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingFixedAssetController extends Controller
{
    /**
     * Display a listing of fixed assets and their depreciation schedule.
     */
    public function index(Request $request): View
    {
        $tahun = $request->integer('tahun') ?: now()->year;
        
        $assets = AccountingFixedAsset::orderBy('tanggal_perolehan')
            ->orderBy('id')
            ->get()
            ->map(function (AccountingFixedAsset $asset) use ($tahun) {
                $depreciation = $asset->calculateDepreciationForYear($tahun);
                // Dynamically attach computed fields for the view
                $asset->rate = $depreciation['rate'];
                $asset->years = $depreciation['years'];
                $asset->prior_months = $depreciation['prior_months'];
                $asset->prior_depreciation = $depreciation['prior_depreciation'];
                $asset->current_months = $depreciation['current_months'];
                $asset->current_depreciation = $depreciation['current_depreciation'];
                $asset->accumulated_depreciation = $depreciation['accumulated_depreciation'];
                $asset->book_value = $depreciation['book_value'];
                return $asset;
            });

        $totalHarga = $assets->sum('harga_perolehan');
        $totalDepreciationYear = $assets->sum('current_depreciation');
        $totalAccumulated = $assets->sum('accumulated_depreciation');
        $totalBookValue = $assets->sum('book_value');

        return view('akuntansi.fixed-assets.index', [
            'assets' => $assets,
            'tahun' => $tahun,
            'totalHarga' => $totalHarga,
            'totalDepreciationYear' => $totalDepreciationYear,
            'totalAccumulated' => $totalAccumulated,
            'totalBookValue' => $totalBookValue,
            'kelompokOptions' => AccountingFixedAsset::KELOMPOK_LABELS,
        ]);
    }

    /**
     * Store a newly created fixed asset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kelompok' => ['required', 'in:I,II,III,IV,bangunan_permanen,bangunan_semi'],
            'tanggal_perolehan' => ['required', 'date'],
            'harga_perolehan' => ['required', 'numeric', 'min:0.01'],
            'metode' => ['required', 'in:garis_lurus,saldo_menurun'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        AccountingFixedAsset::create($data);

        return redirect()->route('akuntansi.fixed-assets.index')
            ->with('status', 'Aset tetap baru berhasil ditambahkan.');
    }

    /**
     * Remove the specified fixed asset from storage.
     */
    public function destroy(AccountingFixedAsset $fixedAsset): RedirectResponse
    {
        $fixedAsset->delete();

        return redirect()->route('akuntansi.fixed-assets.index')
            ->with('status', 'Aset tetap berhasil dihapus.');
    }
}
