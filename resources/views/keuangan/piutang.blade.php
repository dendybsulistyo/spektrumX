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
            #industry-piutang .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 12px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-piutang .in-btn:hover { background: var(--color-accent-600); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $jenisTag = fn (string $statusBayar) => $statusBayar === 'hutang' ? 'tag tag-hutang' : 'tag tag-dp';
    @endphp

    <div id="industry-piutang"
         x-data="{ modalOpen: false, type: '', id: null, noOrder: '', jenis: '', sisa: '', caraBayar: 'tunai', noReferensi: '' }">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Hutang VIP</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 28px; line-height: 1;">{{ $fmt($totalHutang) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Sisa DP</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 28px; line-height: 1;">{{ $fmt($totalDp) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total Piutang Berjalan</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 28px; line-height: 1;">{{ $fmt($totalHutang + $totalDp) }}</div>
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
                                                @click="modalOpen = true; type = '{{ $row['type'] }}'; id = {{ $row['id'] }}; noOrder = '{{ $row['no_order'] }}'; jenis = '{{ $row['status_bayar'] }}'; sisa = '{{ number_format($row['jumlah_piutang'], 0, ',', '.') }}'; caraBayar = 'tunai'; noReferensi = ''">
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
                <form method="POST" :action="jenis === 'hutang' ? `/kasir/${type}/${id}/lunasi-hutang` : `/kasir/${type}/${id}/lunasi`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Pelunasan — <span x-text="noOrder"></span></h3>
                    <p class="text-sm text-gray-600">Sisa piutang: <span class="font-semibold" x-text="`Rp ${sisa}`"></span></p>

                    <div>
                        <x-input-label value="Cara Bayar" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="tunai" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Tunai</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="qris" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">QRIS</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="transfer" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Transfer</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="caraBayar !== 'tunai'" x-cloak>
                        <x-input-label value="No. Referensi" />
                        <input type="text" name="no_referensi" x-model="noReferensi" maxlength="50"
                               placeholder="ID transaksi QRIS / 4 digit terakhir rekening tujuan"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
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
