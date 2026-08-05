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
                    <table class="table" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th>No order</th><th>Tipe</th><th>Customer</th><th>Alasan</th><th>Diajukan oleh</th><th>Diajukan pada</th><th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row->NoOrder }}</td>
                                    <td>
                                        <span class="tag tag-neutral">{{ ucfirst($row->order_type) }}</span>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 2px;">{{ $row->kind === 'rework_batal' ? 'Batal antrian' : 'Pembatalan nota' }}</div>
                                    </td>
                                    <td>{{ $row->customer?->NmCust ? ucwords(mb_strtolower($row->customer->NmCust)) : '-' }}</td>
                                    <td class="text-muted" style="max-width: 220px;">{{ $row->cancel_reason }}</td>
                                    <td class="text-muted">{{ $row->cancelRequestedBy?->name ?? '-' }}</td>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row->cancel_requested_at?->format('d M Y H:i') }}</td>
                                    <td style="text-align: right;">
                                        @if ($row->kind === 'rework_batal')
                                            @can('order-rework.approve')
                                                <div style="display: inline-flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                                    <span class="text-muted" style="font-size: 11px;">Batal dari antrian — uang dikembalikan ke customer</span>
                                                    <form method="POST" action="{{ route('order-rework.approve', $row->id) }}"
                                                          onsubmit="return confirm('Setujui pembatalan order {{ $row->NoOrder }}? Uang yang sudah dibayar akan dikembalikan/dicatat sebagai refund.')">
                                                        @csrf
                                                        <button type="submit" class="in-btn">Setujui + Refund</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('order-rework.reject', $row->id) }}"
                                                          onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $row->NoOrder }}?')">
                                                        @csrf
                                                        <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size: 13px;">Tidak berwenang</span>
                                            @endcan
                                        @else
                                            @can('order-' . $row->order_type . '.approve-cancel')
                                                <div style="display: inline-flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                                    <form method="POST" action="{{ route('order-' . $row->order_type . '.approve-cancel', $row->id) }}"
                                                          onsubmit="return confirm('Setujui pembatalan order {{ $row->NoOrder }} dengan nota pengganti? Nota lama akan dihanguskan.')">
                                                        @csrf
                                                        <input type="hidden" name="resolution" value="nota_pengganti">
                                                        <button type="submit" class="in-btn">Setujui + Nota Pengganti</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('order-' . $row->order_type . '.approve-cancel', $row->id) }}"
                                                          onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $row->NoOrder }}? Tidak akan ada nota pengganti.')">
                                                        @csrf
                                                        <input type="hidden" name="resolution" value="batal_total">
                                                        <button type="submit" class="in-btn in-btn-danger">Setujui Batal Total</button>
                                                    </form>
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
                                <tr><td colspan="7" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada pengajuan pembatalan yang menunggu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
