<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Approval Hutang</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-hutang-approval { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-hutang-approval .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-hutang-approval .in-btn:hover { background: var(--color-accent-600); }
            #industry-hutang-approval .in-btn-ghost { background: transparent; color: var(--color-text); border-color: var(--color-divider); }
        </style>
    @endpush

    <div id="industry-hutang-approval">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

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
                    Semua <b>pengajuan hutang customer VIP yang melebihi plafon</b> (Indoor/Outdoor/Artwork) yang menunggu persetujuan.
                </p>
            </div>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 980px;">
                        <thead>
                            <tr>
                                <th>No order</th><th>Tipe</th><th>Customer</th><th>Total nota</th><th>Plafon</th><th>Sisa plafon</th><th>Catatan</th><th>Diajukan oleh</th><th>Diajukan pada</th><th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $batas = (float) ($row->customer?->limit?->Batas ?? 0);
                                    $berjalan = (float) ($row->customer?->limit?->Total ?? 0);
                                    $sisa = $batas - $berjalan;
                                    $amount = $row->hutangAmount();
                                @endphp
                                <tr>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row->NoOrder }}</td>
                                    <td><span class="tag tag-neutral">{{ ucfirst($row->order_type) }}</span></td>
                                    <td>{{ $row->customer?->NmCust ? ucwords(mb_strtolower($row->customer->NmCust)) : '-' }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                                    <td class="text-muted">Rp {{ number_format($batas, 0, ',', '.') }}</td>
                                    <td style="color: #991b1b; font-weight: 600;">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                    <td class="text-muted" style="max-width: 220px;">{{ $row->hutang_catatan ?: '-' }}</td>
                                    <td class="text-muted">{{ $row->hutangRequestedBy?->name ?? '-' }}</td>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row->hutang_requested_at?->format('d M Y H:i') }}</td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                            <form method="POST" action="{{ route('kasir.hutang.approve', ['type' => $row->order_type, 'id' => $row->id]) }}"
                                                  onsubmit="return confirm('Setujui hutang Rp {{ number_format($amount, 0, ',', '.') }} untuk order {{ $row->NoOrder }}?')">
                                                @csrf
                                                <button type="submit" class="in-btn">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('kasir.hutang.reject', ['type' => $row->order_type, 'id' => $row->id]) }}"
                                                  onsubmit="return confirm('Tolak pengajuan hutang untuk order {{ $row->NoOrder }}?')">
                                                @csrf
                                                <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada pengajuan hutang yang menunggu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
