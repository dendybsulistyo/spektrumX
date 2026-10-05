@php
    $order = $items->first()->order;
    $allOrderItems = $indoorItems->get($order->id, $items);
@endphp
<div class="order-card">
    <x-order-date-rail :date="$order->TglOrder" />
    <div class="order-card-head">
        <div class="order-summary">
            <div class="order-identity">
                <x-order-number :number="$order->NoOrder" />
                <x-macet-badge :show="$order->isMacet()" />
                <span class="order-meta-divider" aria-hidden="true"></span>
                <span class="order-meta-customer">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</span>
            </div>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
            <x-order-rework type="indoor" :order-id="$order->id" :no-order="$order->NoOrder"
                             current-stage="desain" :max-qty="$allOrderItems->sum(fn ($i) => $i->qtyAt('desain'))"
                             :pending="$pendingRework->get('indoor-'.$order->id)"
                             :can-approve="$canApproveRework" :compact="true" />
            @if ($order->cancel_requested_at)
                <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
                @can('order-indoor.approve-cancel')
                    <form method="POST" action="{{ route('order-indoor.approve-cancel', $order->id) }}"
                          onsubmit="return confirm('Setujui pembatalan order {{ $order->NoOrder }} dengan nota pengganti? Nota lama akan dihanguskan.')">
                        @csrf
                        <input type="hidden" name="resolution" value="nota_pengganti">
                        <button type="submit" class="in-btn">Setujui + Nota Pengganti</button>
                    </form>
                    <form method="POST" action="{{ route('order-indoor.approve-cancel', $order->id) }}"
                          onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $order->NoOrder }}? Tidak akan ada nota pengganti.')">
                        @csrf
                        <input type="hidden" name="resolution" value="batal_total">
                        <button type="submit" class="in-btn in-btn-danger">Setujui Batal Total</button>
                    </form>
                    <form method="POST" action="{{ route('order-indoor.reject-cancel', $order->id) }}"
                          onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $order->NoOrder }}?')">
                        @csrf
                        <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @foreach ($items as $item)
        <div id="layout-item-indoor-{{ $item->id }}" class="item-row">
            <div>
                @if ($groupBy !== 'product')
                    <strong>{{ $item->NmProd ?: ($item->produk?->NmProd ?? 'Produk tanpa nama') }}</strong>
                    @if ($item->Judul)
                        <span class="item-meta-divider" aria-hidden="true"></span>
                    @endif
                @endif
                @if ($item->Judul && $groupBy !== 'product')
                    {{ $item->Judul }}
                @elseif ($item->Judul)
                    <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">{{ $item->Judul }}</span>
                @endif
                @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                    <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                        ({{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }} cm)
                    </span>
                @endif
            </div>
            <div style="display: inline-flex; align-items: center; gap: var(--space-3);">
                <span class="progress-tag">Progres Desain: {{ $item->Qty - $item->qtyAt('desain') }}/{{ $item->Qty }}</span>
                @can('order-desain.manage')
                    <input type="checkbox" @change="toggle('indoor', {{ $item->id }}, $event.target.checked)" title="Pilih untuk kirim massal">
                    <form method="POST" action="{{ route('order-desain.progress', ['indoor', $item->id]) }}"
                          @submit="rememberPosition('layout-item-indoor-{{ $item->id }}')"
                          style="display: flex; align-items: center; gap: 4px;">
                        @csrf
                        <input type="number" id="qty-indoor-{{ $item->id }}" name="qty" min="1" max="{{ $item->qtyAt('desain') }}" value="{{ $item->qtyAt('desain') }}" required
                               class="in-input no-spinner" style="width: 70px;">
                        <button type="submit" class="in-btn">Kirim ke Cetak</button>
                    </form>
                @endcan
            </div>
        </div>
    @endforeach
</div>
