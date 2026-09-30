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
                  x-data="{ type: @js(old('adjustment_type', 'setor_tunai')), position: @js(old('entry_side', 'debet')) }"
                  x-effect="if (type === 'setor_bank') position = 'kredit'"
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
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Posisi</label>
                    <select name="entry_side" x-model="position" required class="w-full rounded border-slate-300 text-sm">
                        <option value="debet" :disabled="type === 'setor_bank'">Debet</option>
                        <option value="kredit">Kredit</option>
                    </select>
                    <p x-show="type === 'setor_bank'" class="mt-1 text-xs text-slate-500">Setor ke Bank wajib Kredit.</p>
                    <x-input-error :messages="$errors->get('entry_side')" class="mt-1" />
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

        <section class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="font-semibold text-slate-800">Riwayat Penyesuaian</h3>
                <p class="mt-1 text-sm text-slate-500">Entri berikut langsung masuk ke Kas Harian, Rekap Kasir per User, dan Laporan Kasir Harian.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Referensi</th><th class="px-5 py-3">Keterangan</th><th class="px-5 py-3">User</th><th class="px-5 py-3 text-right">Debet</th><th class="px-5 py-3 text-right">Kredit</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($adjustments as $entry)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3">{{ ($entry->occurred_at ?? $entry->tanggal)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $entry->no_nota ?: '-' }}</td>
                                <td class="px-5 py-3">{{ $entry->keterangan }}</td>
                                <td class="px-5 py-3">{{ $entry->user?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ number_format($entry->debet, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ number_format($entry->kredit, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada penyesuaian kas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($adjustments->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $adjustments->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
