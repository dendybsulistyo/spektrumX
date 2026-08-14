@props([
    'type',
    'order',
    'items',
    'stage',
    'routeName',
    'nextLabel',
    'pendingRework',
    'canApproveRework',
    'printerNames' => null,
    'outdoorComments' => null,
    'outdoorUnread' => null,
    'manageAbility',
])

<div class="order-card">
    <div class="order-card-head">
        <div style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-heading); font-weight: 600;">
            <x-order-number :number="$order->NoOrder" />
            <x-macet-badge :show="$order->isMacet()" />
            <span class="text-muted" style="font-weight: 400; font-size: 12px;">
                {{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('d-m-y') }}
                &middot; {{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}
            </span>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
            @if ($type === 'outdoor' && $outdoorComments !== null)
                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                     :comments="$outdoorComments->get($order->id, collect())"
                                     :unread="$outdoorUnread->get($order->id, 0)" :compact="true" />
            @endif
            <x-order-rework :type="$type" :order-id="$order->id" :no-order="$order->NoOrder"
                             :current-stage="$stage"
                             :pending="$pendingRework->get($type.'-'.$order->id)"
                             :can-approve="$canApproveRework" :compact="true" />
            @if ($order->cancel_requested_at)
                <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
            @endif
        </div>
    </div>

    @foreach ($items as $item)
        <div class="item-row">
            <div>
                @if ($type === 'outdoor')
                    <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                    <span class="text-muted" style="font-size: 12px;">
                        {{ $item->gabungan ?: ($item->NmFile ?: '-') }}
                    </span>
                @else
                    {{ $item->Judul }}
                    @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                        <span class="text-muted" style="font-size: 12px;">
                            ({{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }} cm)
                        </span>
                    @endif
                @endif
            </div>
            <div style="display: inline-flex; align-items: center; gap: var(--space-3);">
                <span class="progress-tag">{{ $item->qtyAt($stage) }}/{{ $item->Qty }}</span>
                @can($manageAbility)
                    {{-- Qty di sini murni apa yang sudah dikirim maju dari tahap
                         sebelumnya — operator di tahap ini cuma meneruskan
                         semuanya, tidak membagi lagi, jadi inputnya read-only. --}}
                    <form method="POST" action="{{ route($routeName, [$type, $item->id]) }}" style="display: flex; align-items: center; gap: 4px;">
                        @csrf
                        <input type="number" value="{{ $item->qtyAt($stage) }}" disabled
                               class="in-input no-spinner" style="width: 70px;">
                        <button type="submit" class="in-btn">Kirim {{ $nextLabel }}</button>
                    </form>
                @endcan
            </div>
        </div>
    @endforeach
</div>
