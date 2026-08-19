<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Operator Cetak</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-cetak { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-cetak .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 14px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-cetak .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-cetak .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-cetak .in-input { width: 70px; min-height: 28px; padding: 4px 6px; font: inherit; font-size: 13px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-cetak .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-cetak .in-btn:hover { background: var(--color-accent-600); }
            #industry-cetak .order-card { border: 1px solid var(--color-divider); margin-bottom: var(--space-4); }
            #industry-cetak .order-card-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: color-mix(in srgb, var(--color-accent) 5%, transparent); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-cetak .item-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-cetak .item-row:last-child { border-bottom: none; }
            #industry-cetak .progress-tag { font-family: var(--font-heading); font-weight: 600; font-size: 13px; color: var(--color-text-muted, #666); }
        </style>
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorItems->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorItems->count()],
        ];
        $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-cetak">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);" x-data="{ tab: '{{ $initialTab }}' }">
            <div style="display: flex;">
                @foreach ($tabs as $key => $t)
                    <button type="button" @click="tab = '{{ $key }}'" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                        {{ $t['label'] }} ({{ $t['count'] }})
                    </button>
                @endforeach
            </div>

            @foreach (['indoor' => $indoorItems, 'outdoor' => $outdoorItems] as $tabKey => $itemGroups)
                <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif style="margin-top: var(--space-4);">
                    @forelse ($itemGroups as $items)
                        <x-stage-item-card :type="$tabKey" :order="$items->first()->order" :items="$items"
                                            stage="cetak" stage-label="Cetak" route-name="order-cetak.update" next-label="Finishing"
                                            :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                                            :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                                            manage-ability="order-cetak.manage" />
                    @empty
                        <div class="blueprint text-muted" style="padding: var(--space-6); text-align: center;">Tidak ada order di antrian cetak.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
