<x-app-layout>
    <x-slot name="header">
        <h2 class="operator-page-title font-semibold text-xl text-gray-800">Operator Cetak</h2>
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
        <x-operator-workspace-styles />
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorItems->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorItems->count()],
        ];
        $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-cetak" class="operator-queue-viewport">
        <div class="operator-queue-shell" style="max-width:1480px;margin:0 auto;"
             x-data="{
                tab: '{{ $initialTab }}',
                selected: {},
                sending: false,
                get selectedCount() { return Object.keys(this.selected).length; },
                setTab(key) {
                    this.tab = key;
                    this.selected = {};
                    document.querySelectorAll('#industry-cetak input[type=checkbox]').forEach((el) => { el.checked = false; });
                    this.$nextTick(() => this.$refs.orderList?.scrollTo({ top: 0 }));
                    const url = new URL(window.location.href); url.searchParams.set('tab', key); window.history.replaceState({}, '', url);
                },
                toggle(type, id, checked) {
                    const key = type + '-' + id;
                    if (checked) { this.selected[key] = { type, id }; } else { delete this.selected[key]; }
                },
                // Kirim massal ke Finishing, sama seperti Layout Indoor → Cetak.
                async bulkSend() {
                    const entries = Object.values(this.selected);
                    if (entries.length === 0) return;
                    for (const e of entries) {
                        const el = document.getElementById('bulk-qty-' + e.type + '-' + e.id);
                        if (!el.value || Number(el.value) < 1 || Number(el.value) > Number(el.max)) {
                            alert('Qty item yang dicentang harus 1 sampai sisa Qty di Cetak.');
                            el.focus();
                            return;
                        }
                    }
                    if (!confirm(`Kirim ${entries.length} item terpilih ke Finishing?`)) return;
                    this.sending = true;
                    try {
                        for (const e of entries) {
                            const el = document.getElementById('bulk-qty-' + e.type + '-' + e.id);
                            await axios.post(`/order-cetak/${e.type}/${e.id}`, { qty: el.value });
                        }
                        window.location.reload();
                    } catch (err) {
                        alert('Gagal mengirim sebagian item. Muat ulang halaman lalu cek lagi.');
                        this.sending = false;
                    }
                },
             }">
            <div class="operator-queue-workspace">
                <x-operator-queue-toolbar :tabs="$tabs" route-name="order-cetak.index" :group-by="$groupBy">
                    @can('order-cetak.manage')
                        <button type="button" x-show="tab === 'indoor'" class="in-btn" :disabled="selectedCount === 0 || sending" @click="bulkSend()"
                                :style="(selectedCount === 0 || sending) ? 'opacity:0.5; cursor:not-allowed;' : ''">
                            <span x-text="sending ? 'Mengirim...' : 'Kirim Terpilih (' + selectedCount + ') ke Finishing'"></span>
                        </button>
                    @endcan
                </x-operator-queue-toolbar>
                <div x-ref="orderList" class="operator-order-list">
                    @foreach (['indoor' => $indoorItems, 'outdoor' => $outdoorItems] as $tabKey => $itemGroups)
                        <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif>
                            <x-stage-queue-items :type="$tabKey" :item-groups="$itemGroups" :group-by="$groupBy"
                                                 stage="cetak" stage-label="Cetak" route-name="order-cetak.update" next-label="Finishing"
                                                 :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                                                 :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                                                 manage-ability="order-cetak.manage" empty-message="Tidak ada order di antrian cetak."
                                                 :bulk-select="$tabKey === 'indoor'" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <x-queue-scroll-memory key="cetak" />
</x-app-layout>

<!-- wire:ignore -->
