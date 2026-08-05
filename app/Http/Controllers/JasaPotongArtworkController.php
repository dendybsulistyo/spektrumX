<?php

namespace App\Http\Controllers;

use App\Models\KonfigurasiJasaPotongArtwork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JasaPotongArtworkController extends Controller
{
    public function edit(): View
    {
        return view('jasa-potong-artwork.edit', [
            'konfigurasi' => KonfigurasiJasaPotongArtwork::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nilai_x' => ['required', 'numeric', 'min:0'],
        ], [], ['nilai_x' => 'nilai X']);

        KonfigurasiJasaPotongArtwork::current()->update($data);

        return redirect()->route('jasa-potong-artwork.edit')->with('status', 'Nilai X berhasil diperbarui.');
    }
}
