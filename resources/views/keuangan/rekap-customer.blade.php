<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Total Order per Customer</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-rekap-customer { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-rekap-customer">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 320px;">
                        <label>Cari customer (nama / kode)</label>
                        <input type="text" name="search" value="{{ $search }}" class="input" placeholder="misal: Adhitya, ADI034" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Cari
                    </button>
                    @if ($search)
                        <a href="{{ route('keuangan.rekap-customer') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                    @endif
                </form>
            </div>

            @if (! $search)
                <div class="blueprint" style="padding: var(--space-6); text-align: center;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <p class="text-muted" style="margin: 0;">Ketik nama atau kode customer di atas untuk melihat total ordernya (Indoor/Outdoor/Artwork).</p>
                </div>
            @else
                <div class="blueprint" style="padding: var(--space-6);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div style="overflow-x: auto;">
                        <table class="table" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th>Customer</th><th>Kode</th><th>Jumlah Order</th><th style="text-align: right;">Total Nilai Order</th><th style="text-align: right;">Total Dibayar</th><th style="text-align: right;">Sisa (Piutang)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td style="font-family: var(--font-heading); font-weight: 600;">{{ ucwords(mb_strtolower($row['nama'])) }}</td>
                                        <td class="text-muted">
                                            {{ $row['kode'] }}
                                            <div class="text-muted" style="font-size: 11px; margin-top: 2px;">{{ $row['per_tipe'] }}</div>
                                        </td>
                                        <td>{{ $row['jumlah_order'] }}</td>
                                        <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['total_nilai']) }}</td>
                                        <td style="text-align: right;">{{ $fmt($row['total_dibayar']) }}</td>
                                        <td style="text-align: right; {{ $row['total_piutang'] > 0 ? 'color: #991b1b;' : '' }}">{{ $row['total_piutang'] > 0 ? $fmt($row['total_piutang']) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada customer dengan nama/kode "{{ $search }}".</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
