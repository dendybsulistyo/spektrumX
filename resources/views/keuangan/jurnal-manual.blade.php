<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Jurnal Manual</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-jurnal-manual { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-jurnal-manual .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-jurnal-manual .in-btn:hover { background: var(--color-accent-600); }
            #industry-jurnal-manual .in-btn-danger { background: #991b1b; border-color: #991b1b; }
            #industry-jurnal-manual .in-btn-danger:hover { background: #7f1d1d; }
            #industry-jurnal-manual .tag-posted { background: #d1fae5; color: #065f46; }
            #industry-jurnal-manual .tag-dibatalkan { background: #f3f4f6; color: #6b7280; text-decoration: line-through; }
            #industry-jurnal-manual .lines-sub { font-size: 13px; }
            #industry-jurnal-manual .lines-sub td { padding-top: 2px; padding-bottom: 2px; border: none !important; }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-jurnal-manual"
         x-data="{
            formOpen: false,
            tanggal: '{{ now()->format('Y-m-d') }}',
            keterangan: '',
            lines: [{ akun: '', posisi: 'debet', jumlah: '' }, { akun: '', posisi: 'kredit', jumlah: '' }],
            rupiah(value) { return value === null || value === '' ? '' : Number(value).toLocaleString('id-ID'); },
            angka(value) { return Number(String(value).replace(/\D/g, '')) || 0; },
            addLine() { this.lines.push({ akun: '', posisi: 'debet', jumlah: '' }); },
            removeLine(idx) { if (this.lines.length > 2) this.lines.splice(idx, 1); },
            totalDebet() { return this.lines.filter(l => l.posisi === 'debet').reduce((s, l) => s + (parseFloat(l.jumlah) || 0), 0); },
            totalKredit() { return this.lines.filter(l => l.posisi === 'kredit').reduce((s, l) => s + (parseFloat(l.jumlah) || 0), 0); },
            isBalanced() { return this.totalDebet() > 0 && Math.abs(this.totalDebet() - this.totalKredit()) < 0.01; },
            resetForm() { this.tanggal = '{{ now()->format('Y-m-d') }}'; this.keterangan = ''; this.lines = [{ akun: '', posisi: 'debet', jumlah: '' }, { akun: '', posisi: 'kredit', jumlah: '' }]; },
         }">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4); display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 160px;">
                        <label>Dari tanggal</label>
                        <input type="date" name="from" value="{{ $from }}" class="input">
                    </div>
                    <div class="field" style="width: 160px;">
                        <label>Sampai tanggal</label>
                        <input type="date" name="to" value="{{ $to }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.jurnal-manual') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                </form>
                @can('keuangan.jurnal-manual')
                    <button type="button" class="btn btn-primary blueprint" style="height: 38px;" @click="resetForm(); formOpen = true">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>+ Jurnal Penyesuaian
                    </button>
                @endcan
            </div>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 780px;">
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Keterangan</th><th style="text-align: right;">Total</th><th>Status</th><th>Diposting oleh</th>
                                @can('keuangan.jurnal-manual')<th style="text-align: right;">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                @php $jm = $entry['model']; @endphp
                                <tr>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $jm->tanggal->format('d M Y') }}</td>
                                    <td>{{ $jm->keterangan }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($entry['total']) }}</td>
                                    <td>
                                        @if ($jm->status === 'posted')
                                            <span class="tag tag-posted">Posted</span>
                                        @else
                                            <span class="tag tag-dibatalkan">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $jm->user?->name ?? '-' }}</td>
                                    @can('keuangan.jurnal-manual')
                                        <td style="text-align: right;">
                                            @if ($jm->status === 'posted')
                                                <form method="POST" action="{{ route('keuangan.jurnal-manual.batalkan', $jm) }}"
                                                      onsubmit="return confirm('Batalkan jurnal ini? Sistem akan posting jurnal balik (reversing entry).')">
                                                    @csrf
                                                    <button type="submit" class="in-btn in-btn-danger">Batalkan</button>
                                                </form>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                                <tr class="lines-sub">
                                    <td></td>
                                    <td colspan="{{ auth()->user()->hasPermission('keuangan.jurnal-manual') ? 4 : 3 }}">
                                        @foreach ($entry['lines'] as $line)
                                            <div class="text-muted">
                                                {{ $line['nama'] }} —
                                                @if ($line['debet'] > 0) D {{ $fmt($line['debet']) }} @else K {{ $fmt($line['kredit']) }} @endif
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada jurnal manual pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @can('keuangan.jurnal-manual')
            <div x-show="formOpen" x-cloak @keydown.escape.window="formOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="formOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

                <div class="relative bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <form method="POST" action="{{ route('keuangan.jurnal-manual.store') }}" class="p-5 space-y-4">
                        @csrf
                        <h3 class="font-semibold text-gray-900">Jurnal Penyesuaian</h3>

                        <div>
                            <x-input-label value="Tanggal" />
                            <input type="date" name="tanggal" x-model="tanggal" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div>
                            <x-input-label value="Keterangan" />
                            <input type="text" name="keterangan" x-model="keterangan" required maxlength="255"
                                   placeholder="misal: Penyusutan alat kantor Juli 2026" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div>
                            <x-input-label value="Baris Jurnal" />
                            <div class="mt-2 space-y-2">
                                <template x-for="(line, idx) in lines" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <select :name="`lines[${idx}][akun]`" x-model="line.akun" required class="rounded-md border-gray-300 text-sm flex-1 min-w-0">
                                            <option value="">Pilih akun</option>
                                            @foreach ($akunOptions as $akun)
                                                <option value="{{ $akun->NoAkun }}">{{ $akun->NoAkun }} — {{ $akun->NmAkun }}</option>
                                            @endforeach
                                        </select>
                                        <select :name="`lines[${idx}][posisi]`" x-model="line.posisi" class="rounded-md border-gray-300 text-sm w-28 shrink-0">
                                            <option value="debet">Debet</option>
                                            <option value="kredit">Kredit</option>
                                        </select>
                                        <input type="text" inputmode="numeric" :name="`lines[${idx}][jumlah]`" :value="rupiah(line.jumlah)" @input="line.jumlah = angka($event.target.value)" required
                                               placeholder="Jumlah" class="rounded-md border-gray-300 text-sm w-32 shrink-0 no-spinner">
                                        <button type="button" @click="removeLine(idx)" x-show="lines.length > 2"
                                                class="text-red-600 text-sm px-1 shrink-0">&times;</button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="addLine()" class="mt-2 text-xs text-blue-600 hover:underline">+ Tambah baris</button>
                        </div>

                        <div class="text-xs rounded-md p-2" :class="isBalanced() ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">
                            Debet: <span x-text="totalDebet().toLocaleString('id-ID')"></span> —
                            Kredit: <span x-text="totalKredit().toLocaleString('id-ID')"></span>
                            <span x-show="!isBalanced()"> (belum balance)</span>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="formOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                            <button type="submit" :disabled="!isBalanced()" :class="isBalanced() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                                    class="px-4 py-2 text-white text-sm font-medium rounded-md">Posting</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
