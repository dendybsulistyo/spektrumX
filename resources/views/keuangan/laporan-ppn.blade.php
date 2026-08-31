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
        $fmt = fn ($n) => 'Rp '.number_format((float) $n, floor((float) $n) == (float) $n ? 0 : 2, ',', '.');
    @endphp

    <div id="industry-ppn">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 160px;">
                        <label>Bulan laporan</label>
                        <input type="month" name="periode" value="{{ $periode }}" class="input">
                    </div>
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
                    <a href="{{ route('keuangan.laporan-ppn.export', ['periode' => $periode, 'dari' => $dari, 'sampai' => $sampai, 'rate' => $rate]) }}" class="btn btn-secondary" style="height: 36px; margin-left: auto;">Export CSV</a>
                </form>
                <p class="text-muted" style="font-size: 13px; margin: var(--space-3) 0 0;">
                    Pilih hanya transaksi yang akan dilaporkan pada PPN masa ini. DPP dihitung dari asumsi harga jual sudah termasuk PPN ({{ $rate }}%), sehingga DPP = Total ÷ (1 + tarif) dan PPN = Total − DPP.
                </p>
                @if ($laporanFinal?->status === 'final')
                    <p class="mt-3 rounded-md bg-green-50 px-3 py-2 text-sm font-semibold text-green-800">
                        ✓ Laporan PPN Final {{ $periode }} — histori terkunci sejak {{ $laporanFinal->finalized_at?->format('d M Y H:i') }}.
                    </p>
                @elseif ($laporanFinal)
                    <p class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Draft PPN {{ $periode }} tersimpan. Anda masih dapat mengubah pilihan transaksi.</p>
                @endif
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

            <form method="POST" action="{{ route('keuangan.laporan-ppn.draft') }}" class="blueprint" style="padding: var(--space-6);"
                  x-data="{
                    selected: @js($selectedKeys),
                    entries: @js($rows->mapWithKeys(fn ($row) => [$row['key'] => ['total' => $row['total'], 'dpp' => $row['dpp'], 'ppn' => $row['ppn']]])),
                    get selectedEntries() { return this.selected.map((key) => this.entries[key]).filter(Boolean); },
                    get selectedTotal() { return this.selectedEntries.reduce((sum, row) => sum + Number(row.total), 0); },
                    get selectedDpp() { return this.selectedEntries.reduce((sum, row) => sum + Number(row.dpp), 0); },
                    get selectedPpn() { return this.selectedEntries.reduce((sum, row) => sum + Number(row.ppn), 0); },
                    format(value) { return 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
                    toggleAll(checked) { this.selected = checked ? Object.keys(this.entries) : []; },
                  }">
                @csrf
                <input type="hidden" name="dari" value="{{ $dari }}">
                <input type="hidden" name="sampai" value="{{ $sampai }}">
                <input type="hidden" name="rate" value="{{ $rate }}">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                @can('keuangan.pengaturan')
                    @unless ($laporanFinal?->status === 'final')
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-4); flex-wrap: wrap;">
                            <label class="text-sm" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                                {{-- <input type="checkbox" :checked="selected.length > 0 && selected.length === Object.keys(entries).length" @change="toggleAll($event.target.checked)"> Pilih transaksi --}}
                            </label>
                            <div style="display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                                <p class="text-sm text-muted" style="margin: 0;">
                                    <strong x-text="selected.length"></strong> transaksi dipilih ·
                                    Total <strong x-text="format(selectedTotal)"></strong> ·
                                    DPP <strong x-text="format(selectedDpp)"></strong> ·
                                    PPN <strong x-text="format(selectedPpn)"></strong>
                                </p>
                                <button type="submit" class="btn btn-primary">Draft Laporan PPN Final</button>
                            </div>
                        </div>
                    @endunless
                @endcan
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th>Tanggal Lunas</th><th>No order</th><th>Tipe</th><th>Customer</th>
                                <th style="text-align: right;">Total</th><th style="text-align: right;">DPP</th><th style="text-align: right;">PPN</th>
                                @unless ($laporanFinal?->status === 'final')
                                    <th style="width: 62px; text-align: center;">
                                        <label title="Check All" style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                            <input type="checkbox" :checked="selected.length > 0 && selected.length === Object.keys(entries).length" @change="toggleAll($event.target.checked)">
                                            
                                        </label>
                                    </th>
                                @endunless
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
                                    @unless ($laporanFinal?->status === 'final')
                                        <td style="text-align: center;"><input data-ppn-row type="checkbox" name="selected[]" value="{{ $row['key'] }}" x-model="selected"></td>
                                    @endunless
                                </tr>
                            @empty
                                <tr><td colspan="{{ $laporanFinal?->status === 'final' ? 7 : 8 }}" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order lunas pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @can('keuangan.pengaturan')
                @if ($laporanFinal?->status === 'draft')
                    <form method="POST" action="{{ route('keuangan.laporan-ppn.finalkan', $laporanFinal) }}" onsubmit="return confirm('Kunci laporan PPN ini? Setelah final, daftar transaksi dan nilai laporan tidak dapat diubah.')">
                        @csrf
                        <button type="submit" class="btn" style="align-self: flex-start; background: #166534; color: white; border-color: #166534;">Kunci menjadi Laporan PPN Final</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
