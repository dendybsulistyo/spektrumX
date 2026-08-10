<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Piutang</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-piutang { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-piutang .tag-hutang { background: #fee2e2; color: #991b1b; }
            #industry-piutang .tag-dp { background: #dbeafe; color: #1e3a8a; }
            #industry-piutang .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-piutang .in-btn:hover { background: var(--color-accent-600); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $jenisTag = fn (string $statusBayar) => $statusBayar === 'hutang' ? 'tag tag-hutang' : 'tag tag-dp';
    @endphp

    <div id="industry-piutang"
         x-data="{
            modalOpen: false, type: '', id: null, noOrder: '', jenis: '', sisa: '', sisaRaw: 0,
            rincian: [{ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }],
            get rincianTotal() { return this.rincian.reduce((sum, r) => sum + Number(r.jumlah || 0), 0); },
            get rincianDiff() { return Math.round((this.sisaRaw - this.rincianTotal) * 100) / 100; },
            // POS-style: paying more than the sisa is fine — the excess is
            // change handed back, not an error.
            get rincianKembalian() { return this.rincianDiff < 0 ? Math.abs(this.rincianDiff) : 0; },
            get rincianError() {
                if (this.rincianDiff <= 0) return '';
                return `Kurang Rp ${this.rincianDiff.toLocaleString('id-ID')}.`;
            },
            addRincian() { this.rincian.push({ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }); },
            removeRincian(i) { this.rincian.splice(i, 1); },
         }">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Hutang VIP</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalHutang) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Sisa DP</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalDp) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total Piutang Berjalan</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalHutang + $totalDp) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Daftar Piutang</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th>No order</th><th>Tipe</th><th>Customer</th><th>Jenis</th><th style="text-align: right;">Total order</th><th style="text-align: right;">Sisa piutang</th><th>Sejak</th><th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row['no_order'] }}</td>
                                    <td><span class="tag tag-neutral">{{ $row['tipe'] }}</span></td>
                                    <td>{{ $row['customer'] }}</td>
                                    <td><span class="{{ $jenisTag($row['status_bayar']) }}">{{ $row['jenis'] }}</span></td>
                                    <td style="text-align: right;" class="text-muted">{{ $fmt($row['total']) }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['jumlah_piutang']) }}</td>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row['sejak']?->format('d M Y') ?? '-' }}</td>
                                    <td style="text-align: right;">
                                        <button type="button" class="in-btn"
                                                @click="modalOpen = true; type = '{{ $row['type'] }}'; id = {{ $row['id'] }}; noOrder = '{{ $row['no_order'] }}'; jenis = '{{ $row['status_bayar'] }}'; sisa = '{{ number_format($row['jumlah_piutang'], 0, ',', '.') }}'; sisaRaw = {{ (float) $row['jumlah_piutang'] }}; rincian = [{ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }]">
                                            Lunasi
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada piutang berjalan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="jenis === 'hutang' ? `/kasir/${type}/${id}/lunasi-hutang` : `/kasir/${type}/${id}/lunasi`" class="p-5 space-y-4"
                      @submit="if (rincianError) { $event.preventDefault(); }">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Pelunasan — <span x-text="noOrder"></span></h3>
                    <p class="text-sm text-gray-600">Sisa piutang: <span class="font-semibold" x-text="`Rp ${sisa}`"></span></p>

                    <div>
                        <x-input-label value="Rincian Pembayaran" />
                        <p class="text-xs text-gray-500 mt-0.5">Bisa dibagi ke beberapa metode sekaligus.</p>

                        <div class="mt-2 space-y-2">
                            <template x-for="(row, idx) in rincian" :key="idx">
                                <div class="flex items-start gap-2 rounded-md border border-gray-200 p-2">
                                    <div class="flex-1 space-y-1.5">
                                        <div class="flex gap-2">
                                            <select :name="`rincian[${idx}][cara_bayar]`" x-model="row.cara_bayar"
                                                    class="rounded-md border-gray-300 text-sm py-1.5 w-28">
                                                <option value="tunai">Tunai</option>
                                                <option value="qris">QRIS</option>
                                                <option value="transfer">Transfer</option>
                                            </select>
                                            <input type="text" inputmode="numeric"
                                                   :value="row.jumlah ? Number(row.jumlah).toLocaleString('id-ID') : ''"
                                                   @input="row.jumlah = $event.target.value.replace(/\D/g, '')"
                                                   placeholder="Jumlah" class="flex-1 rounded-md border-gray-300 text-sm py-1.5">
                                            <input type="hidden" :name="`rincian[${idx}][jumlah]`" :value="row.jumlah">
                                            <button type="button" x-show="rincian.length > 1" @click="removeRincian(idx)"
                                                    class="text-gray-400 hover:text-red-600 px-1">&times;</button>
                                        </div>
                                        <input type="text" x-show="row.cara_bayar !== 'tunai'" x-cloak
                                               :name="`rincian[${idx}][no_referensi]`" x-model="row.no_referensi"
                                               maxlength="50" placeholder="No. referensi QRIS/transfer"
                                               class="w-full rounded-md border-gray-300 text-xs py-1.5">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addRincian()" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                            + Tambah metode pembayaran
                        </button>

                        <p class="text-xs mt-2" :class="rincianError ? 'text-red-600 font-semibold' : (rincianKembalian > 0 ? 'text-green-600 font-semibold' : 'text-gray-400')">
                            Total rincian: <span x-text="rincianTotal.toLocaleString('id-ID')"></span>
                            / <span x-text="sisaRaw.toLocaleString('id-ID')"></span>
                            <span x-show="rincianError" x-text="'— ' + rincianError"></span>
                            <span x-show="rincianKembalian > 0" x-text="'— Kembalian: Rp ' + rincianKembalian.toLocaleString('id-ID')"></span>
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">Konfirmasi Lunasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
