<?php

namespace App\Http\Controllers;

use App\Models\AccountingSupplier;
use App\Models\AccountingOpeningBalance;
use App\Models\JurnalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountingSupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $suppliers = AccountingSupplier::query()
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('kode_bantu', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%")))
            ->orderBy('kode_bantu')->get();

        return view('akuntansi.suppliers.index', compact('suppliers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['kode_bantu'] = $data['kode_bantu'] ?: $this->nextCode();
        $data['saldo_awal'] = (float) ($data['saldo_awal'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        AccountingSupplier::create($data);

        return back()->with('status', "Supplier {$data['kode_bantu']} berhasil ditambahkan.");
    }

    public function update(Request $request, AccountingSupplier $supplier): RedirectResponse
    {
        $data = $this->validated($request, $supplier->kode_bantu);
        $data['saldo_awal'] = (float) ($data['saldo_awal'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        if ($data['kode_bantu'] !== $supplier->kode_bantu && $this->hasAccountingHistory($supplier->kode_bantu)) {
            return back()->with('error', 'Kode supplier yang sudah memiliki saldo awal atau jurnal tidak boleh diubah.');
        }

        $supplier->update($data);

        return back()->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(AccountingSupplier $supplier): RedirectResponse
    {
        if ($this->hasAccountingHistory($supplier->kode_bantu)) {
            return back()->with('error', 'Supplier sudah memiliki saldo awal atau jurnal. Nonaktifkan supplier agar riwayat pembukuan tetap aman.');
        }

        $supplier->delete();

        return back()->with('status', 'Supplier berhasil dihapus.');
    }

    private function hasAccountingHistory(string $code): bool
    {
        return AccountingOpeningBalance::where('kode_bantu', $code)->exists()
            || JurnalEntry::where('KdBantu', $code)->exists();
    }

    /** @return array{kode_bantu:string,nama:string,npwp:?string,alamat:?string,saldo_awal:float,is_active?:bool} */
    private function validated(Request $request, ?string $currentCode = null): array
    {
        return $request->validate([
            'kode_bantu' => ['nullable', 'string', 'regex:/^H-\\d{3}$/', Rule::unique('accounting_suppliers', 'kode_bantu')->ignore($currentCode, 'kode_bantu')],
            'nama' => ['required', 'string', 'max:120'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'saldo_awal' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function nextCode(): string
    {
        $last = AccountingSupplier::query()->where('kode_bantu', 'like', 'H-%')->orderByDesc('kode_bantu')->value('kode_bantu');
        $number = $last ? (int) substr($last, 2) + 1 : 1;

        return 'H-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
