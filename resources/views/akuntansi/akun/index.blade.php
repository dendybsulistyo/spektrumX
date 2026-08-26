<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Kode Akun</h2></x-slot>

    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('status'))<div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Chart of Accounts</p><h3 class="mt-1 text-lg font-semibold text-gray-900">Kode akun Ledger Spektra</h3><p class="mt-1 text-sm text-gray-500">Akun D/K dapat diposting. Baris bertanda “Header” hanya untuk pengelompokan laporan.</p></div>
                <form method="GET" class="flex gap-2"><input name="search" value="{{ $search }}" class="rounded-lg border-gray-300 text-sm" placeholder="Cari kode atau nama akun"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Cari</button></form>
            </div>
        </section>

        @can('keuangan.pengaturan')
            <details class="rounded-xl border border-gray-200 bg-white shadow-sm"><summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-indigo-700">+ Tambah Kode Akun</summary>
                <form method="POST" action="{{ route('akuntansi.akun.store') }}" class="grid gap-3 border-t border-gray-100 p-5 md:grid-cols-4">@csrf
                    <input name="NoAkun" value="{{ old('NoAkun') }}" maxlength="6" class="rounded-lg border-gray-300 text-sm" placeholder="Kode, contoh 63016" required>
                    <input name="NmAkun" value="{{ old('NmAkun') }}" maxlength="60" class="rounded-lg border-gray-300 text-sm" placeholder="Nama akun" required>
                    <select name="TipeDK" class="rounded-lg border-gray-300 text-sm"><option value="D">Debet normal</option><option value="K">Kredit normal</option><option value="-">Header</option></select>
                    <div class="flex gap-2"><select name="TipeNL" class="flex-1 rounded-lg border-gray-300 text-sm"><option value="N">Neraca</option><option value="L">Laba Rugi</option><option value="-">Header</option></select><button class="rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white">Simpan</button></div>
                    @error('NoAkun')<p class="text-sm text-red-600 md:col-span-4">{{ $message }}</p>@enderror
                </form>
            </details>
        @endcan

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-indigo-50 text-xs uppercase tracking-wide text-indigo-700"><tr><th class="px-5 py-3 text-left">Kode</th><th class="px-5 py-3 text-left">Nama Akun</th><th class="px-5 py-3 text-left">Saldo Normal</th><th class="px-5 py-3 text-left">Laporan</th>@can('keuangan.pengaturan')<th class="px-5 py-3 text-right">Aksi</th>@endcan</tr></thead><tbody class="divide-y divide-gray-100">
            @forelse ($accounts as $akun)
                @php
                    $isHeader = $akun->TipeDK === '-';
                    $isLvl1 = $isHeader && str_ends_with($akun->NoAkun, '0000');
                    $isLvl2 = $isHeader && !$isLvl1 && str_ends_with($akun->NoAkun, '000');
                @endphp
                <tr class="{{ $isLvl1 ? 'bg-amber-100/70 font-bold text-gray-900' : ($isLvl2 ? 'bg-amber-50/50 font-semibold text-gray-800' : 'text-gray-700 bg-white hover:bg-gray-50/50') }}">
                    <td class="px-5 py-3 font-mono">{{ $akun->NoAkun }}</td>
                    <td class="px-5 py-3">
                        <span class="{{ $isHeader ? ($isLvl1 ? '' : 'pl-4') : 'pl-8' }} inline-block">
                            {{ $akun->NmAkun }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        {{ $akun->TipeDK === '-' ? 'Header' : ($akun->TipeDK === 'D' ? 'Debet' : 'Kredit') }}
                    </td>
                    <td class="px-5 py-3">
                        {{ $akun->TipeNL === 'N' ? 'Neraca' : ($akun->TipeNL === 'L' ? 'Laba Rugi' : '-') }}
                    </td>
                    @can('keuangan.pengaturan')
                        <td class="px-5 py-3 text-right">
                            <details class="inline-block text-left">
                                <summary class="cursor-pointer text-indigo-600">Ubah</summary>
                                <form method="POST" action="{{ route('akuntansi.akun.update', $akun) }}" class="absolute right-4 z-10 mt-2 grid w-72 gap-2 rounded-lg border bg-white p-3 shadow-lg">
                                    @csrf 
                                    @method('PUT')
                                    <input name="NoAkun" value="{{ $akun->NoAkun }}" class="rounded border-gray-300 text-sm" required>
                                    <input name="NmAkun" value="{{ $akun->NmAkun }}" class="rounded border-gray-300 text-sm" required>
                                    <select name="TipeDK" class="rounded border-gray-300 text-sm">
                                        <option value="D" @selected($akun->TipeDK==='D')>Debet normal</option>
                                        <option value="K" @selected($akun->TipeDK==='K')>Kredit normal</option>
                                        <option value="-" @selected($akun->TipeDK==='-')>Header</option>
                                    </select>
                                    <select name="TipeNL" class="rounded border-gray-300 text-sm">
                                        <option value="N" @selected($akun->TipeNL==='N')>Neraca</option>
                                        <option value="L" @selected($akun->TipeNL==='L')>Laba Rugi</option>
                                        <option value="-" @selected($akun->TipeNL==='-')>Header</option>
                                    </select>
                                    <button class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Simpan perubahan</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('akuntansi.akun.destroy', $akun) }}" class="inline" onsubmit="return confirm('Hapus akun ini?')">
                                @csrf 
                                @method('DELETE')
                                <button class="ml-3 text-red-600">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">Kode akun tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody></table></div></section>
    </div></div>
</x-app-layout>
