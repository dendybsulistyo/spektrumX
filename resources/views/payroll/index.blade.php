<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Payroll</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-payroll { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-payroll .tag-draft { background: #fef3c7; color: #92400e; }
            #industry-payroll .tag-dibayar { background: #d1fae5; color: #065f46; }
            #industry-payroll .tag-belum { background: #f3f4f6; color: #6b7280; }
            #industry-payroll .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 12px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-payroll .in-btn:hover { background: var(--color-accent-600); }
            #industry-payroll .in-btn-secondary { background: transparent; color: var(--color-text); border-color: var(--color-divider); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-payroll"
         x-data="{
            gajiOpen: false, gajiUserId: '', gajiNama: '', gajiPokokInput: '', tunjanganInput: '', catatanInput: '',
            bayarOpen: false, bayarSlipId: null, bayarNama: '', bayarTotal: '', potonganInput: '0', bayarCaraBayar: 'tunai', bayarNoReferensi: '',
         }">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap; justify-content: space-between;">
                    <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                        <div class="field" style="width: 180px;">
                            <label>Periode</label>
                            <input type="month" name="periode" value="{{ $periode }}" class="input">
                        </div>
                        <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                            <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Tampilkan
                        </button>
                    </form>
                    @can('payroll.manage')
                        <form method="POST" action="{{ route('payroll.proses') }}">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <button type="submit" class="btn btn-primary blueprint" style="height: 36px;"
                                    onclick="return confirm('Proses gajian draft untuk periode {{ $periode }} bagi semua pegawai yang sudah punya konfigurasi gaji?')">
                                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Proses Gajian ({{ $jumlahBelumDiproses }} pending)
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Total Gaji Terkonfigurasi</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($totalGajiPokok) }}</div>
                    <div class="card-meta">Per bulan, semua pegawai aktif</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Draft Periode Ini</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($totalDraft) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Sudah Dibayar Periode Ini</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($totalDibayar) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th>Pegawai</th><th style="text-align: right;">Gaji Pokok</th><th style="text-align: right;">Tunjangan</th><th style="text-align: right;">Total</th><th>Status ({{ $periode }})</th>
                                @can('payroll.manage')<th style="text-align: right;">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row['nama'] }}</td>
                                    <td style="text-align: right;" class="text-muted">{{ $fmt($row['gaji_pokok']) }}</td>
                                    <td style="text-align: right;" class="text-muted">{{ $fmt($row['tunjangan']) }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['gaji_pokok'] + $row['tunjangan']) }}</td>
                                    <td>
                                        @if (! $row['ada_config'])
                                            <span class="tag tag-belum">Belum diatur</span>
                                        @elseif (! $row['slip'])
                                            <span class="tag tag-belum">Belum diproses</span>
                                        @elseif ($row['slip']->status === 'dibayar')
                                            <span class="tag tag-dibayar">Dibayar {{ $row['slip']->dibayar_at?->format('d M Y') }}</span>
                                        @else
                                            <span class="tag tag-draft">Draft — Rp {{ number_format($row['slip']->total, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    @can('payroll.manage')
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <button type="button" class="in-btn in-btn-secondary"
                                                        @click="gajiOpen = true; gajiUserId = '{{ $row['user_id'] }}'; gajiNama = '{{ $row['nama'] }}'; gajiPokokInput = '{{ (int) $row['gaji_pokok'] }}'; tunjanganInput = '{{ (int) $row['tunjangan'] }}'; catatanInput = ''">
                                                    Atur Gaji
                                                </button>
                                                @if ($row['slip'] && $row['slip']->status === 'draft')
                                                    <button type="button" class="in-btn"
                                                            @click="bayarOpen = true; bayarSlipId = {{ $row['slip']->id }}; bayarNama = '{{ $row['nama'] }}'; bayarTotal = '{{ number_format($row['slip']->total, 0, ',', '.') }}'; potonganInput = '0'; bayarCaraBayar = 'tunai'; bayarNoReferensi = ''">
                                                        Bayar
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @can('payroll.manage')
            {{-- Atur Gaji modal --}}
            <div x-show="gajiOpen" x-cloak @keydown.escape.window="gajiOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="gajiOpen = false" class="absolute inset-0 bg-gray-900/50"></div>
                <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                    <form method="POST" :action="`/payroll/gaji/${gajiUserId}`" class="p-5 space-y-4">
                        @csrf
                        <h3 class="font-semibold text-gray-900">Atur Gaji — <span x-text="gajiNama"></span></h3>
                        <div>
                            <x-input-label value="Gaji Pokok (Rp)" />
                            <input type="number" name="gaji_pokok" x-model="gajiPokokInput" required min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm no-spinner">
                        </div>
                        <div>
                            <x-input-label value="Tunjangan (Rp)" />
                            <input type="number" name="tunjangan" x-model="tunjanganInput" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm no-spinner">
                        </div>
                        <div>
                            <x-input-label value="Catatan (opsional)" />
                            <input type="text" name="catatan" x-model="catatanInput" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="gajiOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Bayar Gaji modal --}}
            <div x-show="bayarOpen" x-cloak @keydown.escape.window="bayarOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="bayarOpen = false" class="absolute inset-0 bg-gray-900/50"></div>
                <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                    <form method="POST" :action="`/payroll/${bayarSlipId}/bayar`" class="p-5 space-y-4">
                        @csrf
                        <h3 class="font-semibold text-gray-900">Bayar Gaji — <span x-text="bayarNama"></span></h3>
                        <p class="text-sm text-gray-600">Total sebelum potongan: <span class="font-semibold" x-text="`Rp ${bayarTotal}`"></span></p>

                        <div>
                            <x-input-label value="Potongan (opsional)" />
                            <input type="number" name="potongan" x-model="potonganInput" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm no-spinner">
                        </div>

                        <div>
                            <x-input-label value="Cara Bayar" />
                            <div class="mt-2 space-y-2">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="tunai" x-model="bayarCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">Tunai</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="qris" x-model="bayarCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">QRIS</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="cara_bayar" value="transfer" x-model="bayarCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-700">Transfer</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="bayarCaraBayar !== 'tunai'" x-cloak>
                            <x-input-label value="No. Referensi" />
                            <input type="text" name="no_referensi" x-model="bayarNoReferensi" maxlength="50" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="bayarOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">Konfirmasi Bayar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
