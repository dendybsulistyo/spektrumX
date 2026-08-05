<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Tutup Buku</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-tutup-buku { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-tutup-buku .tag-closed { background: #fee2e2; color: #991b1b; }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-tutup-buku">
        <div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <p class="text-muted" style="font-size: 14px; margin: 0;">
                    Menutup periode mengunci semua Pengeluaran dan pemrosesan Payroll bertanggal di bulan itu — tidak bisa lagi ditambah, diubah, atau dihapus. Gunakan setelah rekonsiliasi kas &amp; Laba Rugi bulan tersebut selesai dicek.
                </p>
            </div>

            @can('keuangan.tutup-buku')
                <div class="blueprint" style="padding: var(--space-6);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <h4 style="margin: 0 0 var(--space-4);">Tutup Periode Baru</h4>

                    <form method="GET" action="{{ route('keuangan.tutup-buku.preview') }}" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4);">
                        <div class="field" style="width: 180px;">
                            <label>Periode</label>
                            <input type="month" name="periode" value="{{ $previewPeriode ?? $periodeUsulan }}" class="input" max="{{ now()->format('Y-m') }}">
                        </div>
                        <button type="submit" class="btn btn-secondary" style="height: 36px;">Cek Rekap</button>
                    </form>

                    @if ($previewPeriode)
                        <section style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-4); margin-bottom: var(--space-4);">
                            <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                                <div class="card-kicker">Kas Masuk — {{ $previewPeriode }}</div>
                                <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($previewKasMasuk) }}</div>
                            </div>
                            <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                                <div class="card-kicker">Pengeluaran — {{ $previewPeriode }}</div>
                                <div style="font-family: var(--font-heading); font-weight: 600; font-size: 24px; line-height: 1;">{{ $fmt($previewPengeluaran) }}</div>
                            </div>
                        </section>

                        <form method="POST" action="{{ route('keuangan.tutup-buku.store') }}"
                              onsubmit="return confirm('Tutup buku periode {{ $previewPeriode }}? Pengeluaran & payroll bulan ini akan terkunci.')">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $previewPeriode }}">
                            <div class="field" style="margin-bottom: var(--space-3);">
                                <label>Catatan (opsional)</label>
                                <input type="text" name="catatan" maxlength="255" class="input" placeholder="mis. sudah direkonsiliasi dengan mutasi bank">
                            </div>
                            <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Tutup Periode {{ $previewPeriode }}
                            </button>
                        </form>
                    @endif
                </div>
            @endcan

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Periode Tertutup</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 560px;">
                        <thead>
                            <tr><th>Periode</th><th>Ditutup oleh</th><th>Waktu</th><th>Catatan</th>@can('keuangan.tutup-buku')<th style="text-align: right;">Aksi</th>@endcan</tr>
                        </thead>
                        <tbody>
                            @forelse ($closed as $row)
                                <tr>
                                    <td><span class="tag tag-closed">{{ $row->periode }}</span></td>
                                    <td class="text-muted">{{ $row->ditutupOleh?->name ?? '-' }}</td>
                                    <td class="text-muted">{{ $row->ditutup_at->format('d M Y H:i') }}</td>
                                    <td class="text-muted">{{ $row->catatan ?? '-' }}</td>
                                    @can('keuangan.tutup-buku')
                                        <td style="text-align: right;">
                                            <form method="POST" action="{{ route('keuangan.tutup-buku.destroy', $row) }}"
                                                  onsubmit="return confirm('Buka kembali periode {{ $row->periode }}? Transaksi bulan ini akan bisa diubah lagi.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="in-btn in-btn-secondary" style="font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: transparent; border: 1px solid var(--color-divider); cursor: pointer;">Buka Kembali</button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada periode yang ditutup.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
