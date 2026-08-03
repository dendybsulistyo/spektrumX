<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Pengeluaran</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-pengeluaran { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-pengeluaran .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 12px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-pengeluaran .in-btn:hover { background: var(--color-accent-600); }
            #industry-pengeluaran .in-btn-danger { background: #991b1b; border-color: #991b1b; }
            #industry-pengeluaran .in-btn-danger:hover { background: #7f1d1d; }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-pengeluaran"
         x-data="{
            formOpen: false, editId: null,
            tanggal: '{{ now()->format('Y-m-d') }}', kategoriInput: 'bahan_baku', keterangan: '', jumlah: '',
            caraBayarInput: 'tunai', noReferensiInput: '',
            formAction() { return this.editId ? `/pengeluaran/${this.editId}` : '/pengeluaran'; },
         }">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            @can('pengeluaran.manage')
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-primary blueprint" style="height: 38px;"
                            @click="formOpen = true; editId = null; tanggal = '{{ now()->format('Y-m-d') }}'; kategoriInput = 'bahan_baku'; keterangan = ''; jumlah = ''; caraBayarInput = 'tunai'; noReferensiInput = ''">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>+ Catat Pengeluaran
                    </button>
                </div>
            @endcan

            <div class="blueprint" style="padding: var(--space-4);">
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
                    <div class="field" style="width: 200px;">
                        <label>Kategori</label>
                        <select name="kategori" class="input">
                            <option value="">Semua kategori</option>
                            @foreach ($kategoriOptions as $key => $label)
                                <option value="{{ $key }}" @selected($kategori === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('pengeluaran.index') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                </form>
            </div>

            <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-4);">
                @foreach ($totalPerKategori->sortDesc()->take(3) as $kat => $jml)
                    <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                        <div class="card-kicker">{{ $kategoriOptions[$kat] ?? $kat }}</div>
                        <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($jml) }}</div>
                    </div>
                @endforeach
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total Pengeluaran</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($total) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Cara bayar</th><th>No. referensi</th><th style="text-align: right;">Jumlah</th><th>Dicatat oleh</th>
                                @can('pengeluaran.manage')<th style="text-align: right;">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengeluaran as $item)
                                <tr>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $item->tanggal->format('d M Y') }}</td>
                                    <td><span class="tag tag-neutral">{{ $item->kategoriLabel() }}</span></td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td class="text-muted">{{ \App\Models\Pengeluaran::CARA_BAYAR_LABELS[$item->cara_bayar] ?? '-' }}</td>
                                    <td class="text-muted">{{ $item->no_referensi ?? '-' }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($item->jumlah) }}</td>
                                    <td class="text-muted">{{ $item->user?->name ?? '-' }}</td>
                                    @can('pengeluaran.manage')
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <button type="button" class="in-btn"
                                                        @click="formOpen = true; editId = {{ $item->id }}; tanggal = '{{ $item->tanggal->format('Y-m-d') }}'; kategoriInput = '{{ $item->kategori }}'; keterangan = @js($item->keterangan); jumlah = '{{ (int) $item->jumlah }}'; caraBayarInput = '{{ $item->cara_bayar }}'; noReferensiInput = @js($item->no_referensi)">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('pengeluaran.destroy', $item) }}"
                                                      onsubmit="return confirm('Hapus catatan pengeluaran {{ $item->keterangan }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="in-btn in-btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada pengeluaran pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @can('pengeluaran.manage')
            <div x-show="formOpen" x-cloak @keydown.escape.window="formOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="formOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

                <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                    <form method="POST" :action="formAction()" class="p-5 space-y-4">
                        @csrf
                        <template x-if="editId"><input type="hidden" name="_method" value="PUT"></template>
                        <h3 class="font-semibold text-gray-900" x-text="editId ? 'Edit Pengeluaran' : 'Catat Pengeluaran'"></h3>

                        <div>
                            <x-input-label value="Tanggal" />
                            <input type="date" name="tanggal" x-model="tanggal" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div>
                            <x-input-label value="Kategori" />
                            <select name="kategori" x-model="kategoriInput" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @foreach ($kategoriOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label value="Keterangan" />
                            <input type="text" name="keterangan" x-model="keterangan" required maxlength="255"
                                   placeholder="misal: Beli tinta CMYK 4 botol" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div>
                            <x-input-label value="Jumlah (Rp)" />
                            <input type="number" name="jumlah" x-model="jumlah" required min="1" step="1"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm no-spinner">
                        </div>

                        <div>
                            <x-input-label value="Cara Bayar" />
                            <div class="mt-2 space-y-2">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="tunai" x-model="caraBayarInput" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">Tunai</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="qris" x-model="caraBayarInput" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">QRIS</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="transfer" x-model="caraBayarInput" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">Transfer</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="caraBayarInput !== 'tunai'" x-cloak>
                            <x-input-label value="No. Referensi" />
                            <input type="text" name="no_referensi" x-model="noReferensiInput" maxlength="50"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="formOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
