<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Pengambilan Barang</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-pengambilan { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-pengambilan .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 14px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-pengambilan .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-pengambilan .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-pengambilan .in-input { width: 70px; min-height: 28px; padding: 4px 6px; font: inherit; font-size: 13px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-pengambilan .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-pengambilan .in-btn:hover { background: var(--color-accent-600); }
            #industry-pengambilan .order-card { border: 1px solid var(--color-divider); margin-bottom: var(--space-4); }
            #industry-pengambilan .order-card-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: color-mix(in srgb, var(--color-accent) 5%, transparent); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-pengambilan .item-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-pengambilan .item-row:last-child { border-bottom: none; }
            #industry-pengambilan .progress-tag { font-family: var(--font-heading); font-weight: 600; font-size: 13px; color: var(--color-text-muted, #666); }
        </style>
    @endpush

    @if (session('error'))
        <div class="mx-auto" style="max-width: 1480px;">
            <div class="tag tag-danger" style="display: block; padding: var(--space-3);">{{ session('error') }}</div>
        </div>
    @endif

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorItems->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorItems->count()],
        ];
        $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-pengambilan">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);"
             x-data="{
                 tab: '{{ $initialTab }}',
                 penerimaOpen: false,
                 penerimaType: '',
                 penerimaId: null,
                 penerimaQty: 0,
                 penerimaNoOrder: '',
             }"
             @open-penerima-modal="
                 penerimaOpen = true;
                 penerimaType = $event.detail.type;
                 penerimaId = $event.detail.id;
                 penerimaQty = $event.detail.qty;
                 penerimaNoOrder = $event.detail.noOrder;
             ">
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
                                            stage="siap_diambil" stage-label="Siap Diambil" route-name="pengambilan.serahkan" next-label="ke Customer"
                                            :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                                            :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                                            manage-ability="pengambilan.manage" :capture-penerima="true" :show-invoice-link="true" />
                    @empty
                        <div class="blueprint text-muted" style="padding: var(--space-6); text-align: center;">Tidak ada order di antrian pengambilan.</div>
                    @endforelse
                </div>
            @endforeach

            <div x-show="penerimaOpen" x-cloak @keydown.escape.window="penerimaOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: var(--space-4);">
                <div @click="penerimaOpen = false" style="position: absolute; inset: 0; background: rgba(17,24,39,0.5);"></div>
                <div class="blueprint" style="position: relative; background: var(--color-bg); width: 100%; max-width: 420px; padding: var(--space-6);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>

                    <h4 style="margin: 0 0 var(--space-4);">Serahkan ke Customer &mdash; <span x-text="penerimaNoOrder"></span></h4>

                    <form method="POST" :action="`/pengambilan/${penerimaType}/${penerimaId}`" style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @csrf
                        <input type="hidden" name="qty" :value="penerimaQty">

                        <div>
                            <label class="label" style="display: block; margin-bottom: 4px;">Nama Penerima</label>
                            <input type="text" name="nama_penerima" required maxlength="100" class="in-input" style="width: 100%;" placeholder="Nama yang mengambil">
                        </div>

                        <div>
                            <label class="label" style="display: block; margin-bottom: 4px;">Kontak Penerima</label>
                            <input type="text" name="kontak_penerima" required maxlength="50" class="in-input" style="width: 100%;" placeholder="No. HP / kontak">
                        </div>

                        <div class="text-muted" style="font-size: 12px;">
                            Qty diserahkan: <span x-text="penerimaQty"></span> unit
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: var(--space-2); margin-top: var(--space-2);">
                            <button type="button" @click="penerimaOpen = false" class="btn btn-secondary" style="height: 32px; padding: 0 12px;">Batal</button>
                            <button type="submit" class="in-btn">Serahkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
