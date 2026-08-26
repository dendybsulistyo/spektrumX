<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $accounts = Akun::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('NoAkun', 'like', "%{$search}%")
                    ->orWhere('NmAkun', 'like', "%{$search}%");
            }))
            ->orderBy('NoAkun')
            ->get();

        return view('akuntansi.akun.index', compact('accounts', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Akun::create($data);

        return back()->with('status', 'Kode akun berhasil ditambahkan.');
    }

    public function update(Request $request, Akun $akun): RedirectResponse
    {
        $data = $this->validated($request, $akun->NoAkun);

        if ($data['NoAkun'] !== $akun->NoAkun && JurnalEntry::where('NoAkun', $akun->NoAkun)->exists()) {
            return back()->with('error', 'Kode akun yang sudah memiliki jurnal tidak boleh diubah agar riwayat buku besar tetap konsisten.');
        }

        $akun->update($data);

        return back()->with('status', 'Kode akun berhasil diperbarui.');
    }

    public function destroy(Akun $akun): RedirectResponse
    {
        if (JurnalEntry::where('NoAkun', $akun->NoAkun)->exists()) {
            return back()->with('error', 'Akun yang sudah memiliki jurnal tidak boleh dihapus. Nonaktifkan atau gunakan jurnal pembalik.');
        }

        $akun->delete();

        return back()->with('status', 'Kode akun berhasil dihapus.');
    }

    /** @return array{NoAkun:string,NmAkun:string,TipeDK:string,TipeNL:string} */
    private function validated(Request $request, ?string $existingCode = null): array
    {
        return $request->validate([
            'NoAkun' => ['required', 'string', 'regex:/^\\d{5,6}$/', Rule::unique('am__', 'NoAkun')->ignore($existingCode, 'NoAkun')],
            'NmAkun' => ['required', 'string', 'max:60'],
            'TipeDK' => ['required', Rule::in(['D', 'K', '-'])],
            'TipeNL' => ['required', Rule::in(['N', 'L', '-'])],
        ]);
    }
}
