<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Operator Finishing</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-finishing { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-finishing .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 14px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-finishing .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-finishing .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-finishing .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-finishing .in-btn:hover { background: var(--color-accent-600); }
        </style>
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorOrders->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorOrders->count()],
            'artwork' => ['label' => 'Artwork', 'count' => $artworkOrders->count()],
        ];
    @endphp

    <div id="industry-finishing">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);" x-data="{ tab: 'indoor' }">
            <div style="display: flex;">
                @foreach ($tabs as $key => $t)
                    <button type="button" @click="tab = '{{ $key }}'" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                        {{ $t['label'] }} ({{ $t['count'] }})
                    </button>
                @endforeach
            </div>

            @foreach (['indoor' => $indoorOrders, 'outdoor' => $outdoorOrders, 'artwork' => $artworkOrders] as $tabKey => $orders)
                <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif
                     class="blueprint" style="padding: var(--space-6);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div style="overflow-x: auto;">
                        <table class="table" style="min-width: 560px;">
                            <thead>
                                <tr>
                                    <th style="width: 32px;">No</th><th>No order</th><th>Printer</th><th>Tanggal</th><th>Customer</th><th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td style="font-family: var(--font-heading); font-weight: 600;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                <x-order-number :number="$order->NoOrder" />
                                                <x-macet-badge :show="$order->isMacet()" />
                                            </div>
                                        </td>
                                        <td>
                                            @if ($tabKey === 'outdoor')
                                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                    @foreach ($order->items->map(fn ($i) => $i->printerCode())->unique() as $code)
                                                        <x-printer-badge :code="$code" :name="$printerNames[$code] ?? null" />
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                                        <td>{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                @if ($tabKey === 'outdoor')
                                                    <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                         :comments="$outdoorComments->get($order->id, collect())"
                                                                         :unread="$outdoorUnread->get($order->id, 0)" />
                                                @endif
                                                <x-order-rework :type="$tabKey" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                 current-stage="finishing"
                                                                 :pending="$pendingRework->get($tabKey.'-'.$order->id)"
                                                                 :can-approve="$canApproveRework" />
                                                @unless ($pendingRework->has($tabKey.'-'.$order->id))
                                                    <form method="POST" action="{{ route('order-finishing.update', [$tabKey, $order->id]) }}"
                                                          onsubmit="return confirm('Yakin ingin mengirim order {{ $order->NoOrder }} ke QC?')">
                                                        @csrf
                                                        <input type="hidden" name="action" value="selesai">
                                                        <button type="submit" class="in-btn">Kirim QC</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order di antrian finishing.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
