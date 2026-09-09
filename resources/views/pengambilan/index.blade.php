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
            #industry-pengambilan .signature-pad { display:block; width:100%; height:160px; border:1px dashed var(--color-divider); background:#fff; touch-action:none; cursor:crosshair; }
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
                 penerimaItems: [],
                 penerimaNoOrder: '',
             }"
             @open-penerima-modal="
                 penerimaOpen = true;
                 penerimaType = $event.detail.type;
                 penerimaId = $event.detail.id;
                 penerimaItems = $event.detail.items;
                 penerimaQty = penerimaItems[0]?.qty ?? 0;
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
                        <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <input type="hidden" name="qty" :value="penerimaQty">

                        <div>
                            <label class="label" style="display: block; margin-bottom: 4px;">Barang yang diserahkan dalam DO ini</label>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <template x-for="(item, index) in penerimaItems" :key="item.id">
                                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) 90px; align-items: center; gap: 8px;">
                                        <span class="text-muted" style="font-size: 12px; overflow-wrap: anywhere;" x-text="item.label"></span>
                                        <div>
                                            <input type="hidden" :name="`items[${index}][id]`" :value="item.id">
                                            <input type="number" :name="`items[${index}][qty]`" x-model.number="item.qty"
                                                   min="1" :max="item.max" required class="in-input no-spinner" style="width: 90px;"
                                                   :aria-label="`Qty ${item.label}`">
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p class="text-muted" style="margin: 4px 0 0; font-size: 11px;">
                                Semua rincian di atas diterbitkan sebagai satu Delivery Order.
                            </p>
                        </div>

                        <div>
                            <label class="label" style="display: block; margin-bottom: 4px;">Nama Penerima</label>
                            <input type="text" name="nama_penerima" required maxlength="100" class="in-input" style="width: 100%;" placeholder="Nama yang mengambil">
                        </div>

                        <div>
                            <label class="label" style="display: block; margin-bottom: 4px;">Kontak Penerima</label>
                            <input type="text" name="kontak_penerima" required maxlength="50" class="in-input" style="width: 100%;" placeholder="No. HP / kontak">
                        </div>

                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;">
                                <label class="label">Tanda Tangan Penerima</label>
                                <button type="button" id="clear-signature" class="btn btn-secondary" style="height:28px;padding:0 9px;font-size:12px;">Hapus</button>
                            </div>
                            <canvas id="pickup-signature-pad" class="signature-pad" width="600" height="220" aria-label="Area tanda tangan penerima"></canvas>
                            <input type="hidden" name="signature_strokes" id="signature-strokes">
                            <p class="text-muted" style="margin:4px 0 0;font-size:11px;">Minta penerima membubuhkan tanda tangan menggunakan jari atau stylus.</p>
                        </div>

                        <div class="text-muted" style="font-size: 12px;">
                            Jumlah rincian dalam DO: <span x-text="penerimaItems.length"></span>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('pickup-signature-pad');
    const form = canvas?.closest('form');
    const field = document.getElementById('signature-strokes');
    const clear = document.getElementById('clear-signature');
    if (!canvas || !form || !field) return;

    const context = canvas.getContext('2d');
    let strokes = [], drawing = false;
    const reset = () => { strokes = []; context.clearRect(0, 0, canvas.width, canvas.height); };
    const point = (event) => {
        const box = canvas.getBoundingClientRect();
        return [Math.max(0, Math.min(600, (event.clientX - box.left) * 600 / box.width)), Math.max(0, Math.min(220, (event.clientY - box.top) * 220 / box.height))];
    };
    const draw = (from, to) => { context.strokeStyle = '#111827'; context.lineWidth = 3; context.lineCap = 'round'; context.lineJoin = 'round'; context.beginPath(); context.moveTo(...from); context.lineTo(...to); context.stroke(); };
    canvas.addEventListener('pointerdown', event => { event.preventDefault(); canvas.setPointerCapture(event.pointerId); drawing = true; strokes.push([point(event)]); });
    canvas.addEventListener('pointermove', event => { if (!drawing) return; const stroke = strokes[strokes.length - 1]; const next = point(event); draw(stroke[stroke.length - 1], next); stroke.push(next); });
    canvas.addEventListener('pointerup', () => drawing = false);
    canvas.addEventListener('pointercancel', () => drawing = false);
    clear.addEventListener('click', reset);
    window.addEventListener('open-penerima-modal', reset);
    form.addEventListener('submit', event => { if (!strokes.some(stroke => stroke.length > 1)) { event.preventDefault(); alert('Tanda tangan penerima wajib diisi.'); return; } field.value = JSON.stringify(strokes); });
});
</script>
