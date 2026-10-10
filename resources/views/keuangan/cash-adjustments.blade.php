<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Penyesuaian Kas</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5">
        @if (session('success'))
            <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-900 px-6 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-blue-300">Akuntansi · Kas</p>
                <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                    <h3 class="text-lg font-semibold">Input Penyesuaian</h3>
                    <p class="text-sm text-slate-300">Dicatat atas user: <strong class="text-white">{{ $cashier?->name ?? 'Yovita belum ditemukan' }}</strong></p>
                </div>
            </div>

            <form method="POST" action="{{ route('keuangan.cash-adjustments.store') }}"
                  x-data="{ type: @js(old('adjustment_type', 'setor_tunai')) }"
                  class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-6">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Tanggal & Jam</label>
                    <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required class="w-full rounded border-slate-300 text-sm">
                    <x-input-error :messages="$errors->get('occurred_at')" class="mt-1" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Jenis Penyesuaian</label>
                    <select name="adjustment_type" x-model="type" required class="w-full rounded border-slate-300 text-sm">
                        <option value="setor_tunai" @selected(old('adjustment_type') === 'setor_tunai')>Setor Tunai</option>
                        <option value="setor_bank" @selected(old('adjustment_type') === 'setor_bank')>Setor ke Bank</option>
                        <option value="pengeluaran" @selected(old('adjustment_type') === 'pengeluaran')>Pengeluaran</option>
                    </select>
                    <x-input-error :messages="$errors->get('adjustment_type')" class="mt-1" />
                </div>
                {{-- Posisi Debet/Kredit kini otomatis sesuai jenis; akun lawan dipilih di sini. --}}
                <div x-show="type === 'setor_tunai'">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Sumber Dana</label>
                    <select name="cash_source" :required="type === 'setor_tunai'" :disabled="type !== 'setor_tunai'" class="w-full rounded border-slate-300 text-sm">
                        @foreach ($cashSources as $key => $source)
                            <option value="{{ $key }}" @selected(old('cash_source', 'tarik_bank') === $key)>{{ $source['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Kas masuk (Debet).</p>
                    <x-input-error :messages="$errors->get('cash_source')" class="mt-1" />
                </div>
                <div x-show="type === 'pengeluaran'">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Akun Beban</label>
                    <select name="expense_account" :required="type === 'pengeluaran'" :disabled="type !== 'pengeluaran'" class="w-full rounded border-slate-300 text-sm">
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->NoAkun }}" @selected(old('expense_account', $defaultExpense) === $account->NoAkun)>{{ $account->NoAkun }} · {{ $account->NmAkun }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Kas keluar (Kredit).</p>
                    <x-input-error :messages="$errors->get('expense_account')" class="mt-1" />
                </div>
                <div x-show="type === 'setor_bank'">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Tujuan</label>
                    <div class="flex h-[38px] items-center rounded border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">11101 · Bank</div>
                    <p class="mt-1 text-xs text-slate-500">Kas keluar (Kredit).</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nominal</label>
                    <div class="flex rounded border border-slate-300 focus-within:border-blue-600 focus-within:ring-1 focus-within:ring-blue-600">
                        <span class="flex items-center border-r border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">Rp</span>
                        <input type="text" inputmode="numeric" data-rupiah name="amount" value="{{ old('amount') }}" required class="min-w-0 flex-1 rounded-r border-0 text-sm focus:ring-0">
                    </div>
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">No. SO / Referensi</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" maxlength="80" class="w-full rounded border-slate-300 text-sm" placeholder="Opsional">
                </div>
                <div class="md:col-span-2 xl:col-span-6">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Alasan Penyesuaian <span x-show="type === 'setor_bank'" class="font-normal normal-case text-slate-400">(opsional)</span>
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="text" name="reason" value="{{ old('reason') }}" maxlength="255" :required="type !== 'setor_bank'" class="min-w-0 flex-1 rounded border-slate-300 text-sm" :placeholder="type === 'setor_bank' ? 'Opsional' : 'Contoh: koreksi metode pembayaran SO ...'">
                        <button type="submit" @disabled(!$cashier) class="rounded bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">Simpan Penyesuaian</button>
                    </div>
                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                </div>
            </form>
        </section>

        <p class="px-1 text-sm text-slate-500">Setiap penyesuaian otomatis dijurnal dan tampil di <strong>Rekap Kas Harian</strong>, <strong>Rekap Kasir per User</strong>, dan <strong>Laporan Kasir Harian</strong>.</p>
    </div>
</x-app-layout>
