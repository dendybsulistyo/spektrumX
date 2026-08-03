<?php

namespace App\Http\Controllers;

use App\Models\PengaturanKeuangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanKeuanganController extends Controller
{
    public function edit(): View
    {
        return view('keuangan.pengaturan', [
            'pengaturan' => PengaturanKeuangan::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_perusahaan' => ['nullable', 'string', 'max:100'],
            'alamat_perusahaan' => ['nullable', 'string', 'max:255'],
            'npwp_perusahaan' => ['nullable', 'string', 'max:20'],
            'is_pkp' => ['nullable', 'boolean'],
            'tarif_ppn_default' => ['required', 'numeric', 'min:0', 'max:100'],
            'nomor_seri_faktur_terakhir' => ['nullable', 'string', 'max:20'],
        ], [], [
            'nama_perusahaan' => 'nama perusahaan',
            'alamat_perusahaan' => 'alamat perusahaan',
            'npwp_perusahaan' => 'NPWP perusahaan',
            'tarif_ppn_default' => 'tarif PPN default',
            'nomor_seri_faktur_terakhir' => 'nomor seri faktur terakhir',
        ]);

        $data['is_pkp'] = $request->boolean('is_pkp');

        PengaturanKeuangan::current()->update($data);

        return redirect()->route('keuangan.pengaturan.edit')->with('status', 'Pengaturan keuangan tersimpan.');
    }
}
