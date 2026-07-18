<?php

namespace App\Http\Controllers;

use App\Models\KonfigurasiJasaPotong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JasaPotongController extends Controller
{
    public function edit(): View
    {
        return view('jasa-potong.edit', [
            'konfigurasi' => KonfigurasiJasaPotong::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nilai_x' => ['required', 'numeric', 'min:0'],
        ], [], ['nilai_x' => 'nilai X']);

        KonfigurasiJasaPotong::current()->update($data);

        return redirect()->route('jasa-potong.edit')->with('status', 'Nilai X berhasil diperbarui.');
    }
}
