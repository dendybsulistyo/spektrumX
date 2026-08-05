<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Laporan PPN</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-ppn { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-ppn">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 160px;">
                        <label>Dari tanggal</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="input">
                    </div>
                    <div class="field" style="width: 160px;">
                        <label>Sampai tanggal</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="input">
                    </div>
                    <div class="field" style="width: 120px;">
                        <label>Tarif PPN (%)</label>
                        <input type="number" name="rate" value="{{ $rate }}" step="0.01" min="0" max="100" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.laporan-ppn') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                    <a href="{{ route('keuangan.laporan-ppn.export', ['dari' => $dari, 'sampai' => $sampai, 'rate' => $rate]) }}" class="btn btn-secondary" style="height: 36px; margin-left: auto;">Export CSV</a>
                </form>
                <p class="text-muted" style="font-size: 13px; margin: var(--space-3) 0 0;">
                    Laporan ini memuat <strong>semua</strong> order berstatus lunas pada periode ini, tanpa terkecuali — DPP dihitung dari asumsi harga jual sudah termasuk PPN ({{ $rate }}%), sehingga DPP = Total ÷ (1 + tarif) dan PPN = Total − DPP. Sesuaikan tarif di atas kalau berbeda.
                </p>
            </div>

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Total Omzet (termasuk PPN)</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($totalOmzet) }}</div>
                    <div class="card-meta">{{ $rows->count() }} transaksi lunas</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Total DPP</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($totalDpp) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total PPN Keluaran</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($totalPpn) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th>Tanggal Lunas</th><th>No order</th><th>Tipe</th><th>Customer</th>
                                <th style="text-align: right;">Total</th><th style="text-align: right;">DPP</th><th style="text-align: right;">PPN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row['tanggal']?->format('d M Y') }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row['no_order'] }}</td>
                                    <td><span class="tag tag-neutral">{{ $row['tipe'] }}</span></td>
                                    <td>{{ $row['customer'] }}</td>
                                    <td style="text-align: right;">{{ $fmt($row['total']) }}</td>
                                    <td style="text-align: right;" class="text-muted">{{ $fmt($row['dpp']) }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['ppn']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order lunas pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
