<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Approval Batal</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-pembatalan { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-pembatalan .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-pembatalan .in-btn:hover { background: var(--color-accent-600); }
            #industry-pembatalan .in-btn-danger { background: #991b1b; border-color: #991b1b; }
            #industry-pembatalan .in-btn-danger:hover { background: #7f1d1d; }
            #industry-pembatalan .in-btn-ghost { background: transparent; color: var(--color-text); border-color: var(--color-divider); }
            #industry-pembatalan .bt-table td { vertical-align: top; padding-top: 14px; padding-bottom: 14px; }
            #industry-pembatalan .bt-no { font-family: var(--font-heading); font-weight: 600; white-space: nowrap; }
            #industry-pembatalan .bt-sub { font-size: 12px; color: var(--color-text-muted, #6b7280); margin-top: 3px; line-height: 1.4; }
            #industry-pembatalan .bt-reason { max-width: 260px; color: var(--color-text-muted, #6b7280); line-height: 1.45; }
            #industry-pembatalan .bt-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; min-width: 230px; }
            #industry-pembatalan .bt-buttons { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
            #industry-pembatalan .bt-money { font-size: 12px; text-align: right; line-height: 1.45; padding: 6px 10px; border: 1px dashed var(--color-divider); background: #fffbeb; }
            #industry-pembatalan .bt-money b { font-family: var(--font-heading); }
        </style>
    @endpush

    <div id="industry-pembatalan">
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            @if (session('status'))
                <div class="blueprint" style="padding: var(--space-4); background: #d1fae5; border-color: #065f46;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <p style="margin: 0; color: #065f46;">{{ session('status') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="blueprint" style="padding: var(--space-4); background: #fee2e2; border-color: #991b1b;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <p style="margin: 0; color: #991b1b;">{{ session('error') }}</p>
                </div>
            @endif

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <p class="text-muted" style="font-size: 14px; margin: 0;">
                    Semua <b>pengajuan pembatalan order</b> (Indoor/Outdoor/Artwork) yang menunggu persetujuan.
                </p>
            </div>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table bt-table" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th>No order</th><th>Customer</th><th>Alasan</th><th>Diajukan</th><th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="bt-no">{{ $row->NoOrder }}</div>
                                        <div class="bt-sub">
                                            <span class="tag tag-neutral">{{ ucfirst($row->order_type) }}</span>
                                            {{ $row->kind === 'rework_batal' ? 'Batal dari antrian' : 'Pembatalan nota' }}
                                        </div>
                                    </td>
                                    <td>{{ $row->customer?->NmCust ? ucwords(mb_strtolower($row->customer->NmCust)) : '-' }}</td>
                                    <td><div class="bt-reason">{{ $row->cancel_reason }}</div></td>
                                    <td style="white-space: nowrap;">
                                        {{ $row->cancelRequestedBy?->name ?? '-' }}
                                        <div class="bt-sub">{{ $row->cancel_requested_at?->format('d M Y · H:i') }}</div>
                                    </td>
                                    <td style="text-align: right;">
                                        @if ($row->kind === 'rework_batal')
                                            @can('order-rework.approve')
                                                @php
                                                    $dibayar = (float) ($row->jumlah_dibayar ?? 0);
                                                    $piutang = (float) ($row->jumlah_piutang ?? 0);
                                                    $caraLabel = \App\Models\OrderPayment::CARA_BAYAR_LABELS[$row->cara_bayar] ?? ucfirst((string) $row->cara_bayar);
                                                    $rpBatal = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
                                                @endphp
                                                <div class="bt-actions">
                                                    <div class="bt-money">
                                                        @if ($row->metode_bayar === 'hutang' && $piutang > 0)
                                                            Piutang <b>{{ $rpBatal($piutang) }}</b> dihapus<br><span class="text-muted">tidak ada uang keluar</span>
                                                        @elseif ($dibayar > 0)
                                                            Uang dikembalikan <b>{{ $rpBatal($dibayar) }}</b><br><span class="text-muted">via {{ $caraLabel }} (seluruh yang sudah dibayar)</span>
                                                        @else
                                                            Belum ada pembayaran<br><span class="text-muted">tidak ada uang dikembalikan</span>
                                                        @endif
                                                    </div>
                                                    <div class="bt-buttons">
                                                        <form method="POST" action="{{ route('order-rework.reject', $row->id) }}"
                                                              onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $row->NoOrder }}?')">
                                                            @csrf
                                                            <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('order-rework.approve', $row->id) }}"
                                                              onsubmit="return confirm('Setujui pembatalan order {{ $row->NoOrder }}?{{ $dibayar > 0 && $row->metode_bayar !== 'hutang' ? ' Uang '.$rpBatal($dibayar).' dikembalikan via '.$caraLabel.'.' : '' }}')">
                                                            @csrf
                                                            <button type="submit" class="in-btn">{{ $dibayar > 0 && $row->metode_bayar !== 'hutang' ? 'Setujui + Refund' : 'Setujui Batal' }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size: 13px;">Tidak berwenang</span>
                                            @endcan
                                        @else
                                            @can('order-' . $row->order_type . '.approve-cancel')
                                                <div class="bt-actions">
                                                    @if ($row->status_bayar !== 'belum_bayar')
                                                        <form method="POST" action="{{ route('order-' . $row->order_type . '.approve-cancel', $row->id) }}"
                                                              onsubmit="return confirm('Setujui pembatalan order {{ $row->NoOrder }} dengan nota pengganti? Nota lama akan dihanguskan.')">
                                                            @csrf
                                                            <input type="hidden" name="resolution" value="nota_pengganti">
                                                            <button type="submit" class="in-btn">Setujui + Nota Pengganti</button>
                                                        </form>
                                                    @endif
                                                    <x-batal-total-form :order="$row" :type="$row->order_type" />
                                                    <form method="POST" action="{{ route('order-' . $row->order_type . '.reject-cancel', $row->id) }}"
                                                          onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $row->NoOrder }}?')">
                                                        @csrf
                                                        <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size: 13px;">Tidak berwenang</span>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada pengajuan pembatalan yang menunggu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
