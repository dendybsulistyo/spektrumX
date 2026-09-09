<?php

namespace App\Http\Controllers;

use App\Models\PengaturanKeuangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanCetakController extends Controller
{
    public function edit(): View
    {
        return view('pengaturan.cetak-sales-order', [
            'pengaturan' => PengaturanKeuangan::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'auto_print_sales_order' => ['required', 'boolean'],
        ]);

        PengaturanKeuangan::current()->update($data);

        return redirect()->route('pengaturan.cetak-sales-order.edit')
            ->with('status', 'Pengaturan cetak Nota Pesanan / Sales Order tersimpan.');
    }
}
