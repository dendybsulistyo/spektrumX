<x-app-layout>
    <x-slot name="header">
        <h2 class="operator-page-title font-semibold text-xl text-gray-800">Operator Bungkus</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-bungkus { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-bungkus .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 14px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-bungkus .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-bungkus .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-bungkus .in-input { width: 70px; min-height: 28px; padding: 4px 6px; font: inherit; font-size: 13px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-bungkus .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-bungkus .in-btn:hover { background: var(--color-accent-600); }
            #industry-bungkus .order-card { border: 1px solid var(--color-divider); margin-bottom: var(--space-4); }
            #industry-bungkus .order-card-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: color-mix(in srgb, var(--color-accent) 5%, transparent); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-bungkus .item-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-bungkus .item-row:last-child { border-bottom: none; }
            #industry-bungkus .progress-tag { font-family: var(--font-heading); font-weight: 600; font-size: 13px; color: var(--color-text-muted, #666); }
        </style>
        <x-operator-workspace-styles />
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorItems->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorItems->count()],
        ];
        $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-bungkus" class="operator-queue-viewport">
        <div class="operator-queue-shell" style="max-width:1480px;margin:0 auto;"
             x-data="{ tab: '{{ $initialTab }}', setTab(key) { this.tab = key; this.$nextTick(() => this.$refs.orderList?.scrollTo({ top: 0 })); const url = new URL(window.location.href); url.searchParams.set('tab', key); window.history.replaceState({}, '', url); } }">
            <div class="operator-queue-workspace">
                <x-operator-queue-toolbar :tabs="$tabs" route-name="order-bungkus.index" :group-by="$groupBy">
                    <div x-show="tab === 'outdoor'" x-cloak style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('order-bungkus.print-outdoor', ['keterangan' => 1]) }}" target="_blank" rel="noopener"
                       class="in-btn">Cetak + Keterangan</a>
                    <a href="{{ route('order-bungkus.print-outdoor', ['keterangan' => 0]) }}" target="_blank" rel="noopener"
                       class="in-btn" style="background: var(--color-surface); color: var(--color-text); border-color: var(--color-divider);">Cetak Tanpa Keterangan</a>
                    </div>
                </x-operator-queue-toolbar>
                <div x-ref="orderList" class="operator-order-list">
                    @foreach (['indoor' => $indoorItems, 'outdoor' => $outdoorItems] as $tabKey => $itemGroups)
                        <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif>
                            <x-stage-queue-items :type="$tabKey" :item-groups="$itemGroups" :group-by="$groupBy"
                                                 stage="bungkus" stage-label="Bungkus" route-name="order-bungkus.update" next-label="Siap Diambil"
                                                 :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                                                 :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                                                 manage-ability="order-bungkus.manage" empty-message="Tidak ada order di antrian bungkus." />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
