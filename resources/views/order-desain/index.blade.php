<x-app-layout>
    <x-slot name="header">
        <h2 class="operator-page-title font-semibold text-xl text-gray-800">Operator Layout / Desain</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-desain { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-desain .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 14px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-desain .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-desain .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-desain .in-input { width: 100%; min-height: 28px; padding: 4px 6px; font: inherit; font-size: 13px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-desain .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-desain .in-btn:hover { background: var(--color-accent-600); }
            #industry-desain .in-btn-danger { background: var(--color-accent-900); border-color: var(--color-accent-900); }
            #industry-desain .in-btn-danger:hover { background: var(--color-accent-800); }
            #industry-desain .in-btn-ghost { background: transparent; color: var(--color-text); border-color: var(--color-divider); }
            #industry-desain .order-card { border: 1px solid var(--color-divider); margin-bottom: var(--space-4); }
            #industry-desain .order-card-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: color-mix(in srgb, var(--color-accent) 5%, transparent); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-desain .item-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) var(--space-4); border-bottom: 1px solid var(--color-divider); flex-wrap: wrap; }
            #industry-desain .item-row:last-child { border-bottom: none; }
            #industry-desain .progress-tag { font-family: var(--font-heading); font-weight: 600; font-size: 13px; color: var(--color-text-muted, #666); }
            #industry-desain .operator-return-focus { animation: operator-return-focus 2.4s ease-out; }
            @keyframes operator-return-focus {
                0%, 30% { background: #dbeafe; box-shadow: inset 4px 0 0 #2563eb; }
                100% { background: #fff; box-shadow: inset 4px 0 0 transparent; }
            }
        </style>
        <x-operator-workspace-styles />
    @endpush

    @php
        $tabs = [];
        if (auth()->user()->hasPermission('order-indoor.view')) {
            $tabs['indoor'] = ['label' => 'Indoor', 'count' => $indoorItems->count()];
        }
        if (auth()->user()->hasPermission('order-outdoor.view')) {
            $tabs['outdoor'] = ['label' => 'Outdoor', 'count' => $outdoorItems->count() + $outdoorNeedsReply->count()];
        }
        $initialTab = array_key_exists(request('tab'), $tabs) ? request('tab') : array_key_first($tabs);
    @endphp

    <div id="industry-desain">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div x-data="{
                    tab: '{{ $initialTab }}',
                    selected: {},
                    sending: false,
                    pageVersion: '{{ $pageVersion }}',
                    positionKey: 'spektrumX:order-desain:return-position',
                    get selectedCount() { return Object.keys(this.selected).length; },
                    init() {
                        this.restorePosition();
                        setInterval(() => this.pollVersion(), 7000);
                    },
                    rememberPosition(anchorId = null) {
                        const anchor = anchorId ? document.getElementById(anchorId) : null;
                        sessionStorage.setItem(this.positionKey, JSON.stringify({
                            path: window.location.pathname,
                            tab: this.tab,
                            anchorId,
                            anchorTop: anchor ? anchor.getBoundingClientRect().top : null,
                            scrollY: window.scrollY,
                            savedAt: Date.now(),
                        }));
                    },
                    restorePosition() {
                        const raw = sessionStorage.getItem(this.positionKey);
                        if (!raw) return;

                        sessionStorage.removeItem(this.positionKey);

                        let position;
                        try { position = JSON.parse(raw); } catch (error) { return; }
                        if (position.path !== window.location.pathname || Date.now() - position.savedAt > 30000) return;

                        if (position.tab && ['indoor', 'outdoor'].includes(position.tab)) this.tab = position.tab;

                        setTimeout(() => {
                            const anchor = position.anchorId ? document.getElementById(position.anchorId) : null;
                            if (anchor && Number.isFinite(position.anchorTop)) {
                                window.scrollBy({ top: anchor.getBoundingClientRect().top - position.anchorTop, behavior: 'auto' });
                                anchor.classList.add('operator-return-focus');
                                setTimeout(() => anchor.classList.remove('operator-return-focus'), 2500);
                                return;
                            }

                            window.scrollTo({ top: Number(position.scrollY) || 0, behavior: 'auto' });
                        }, 60);
                    },
                    pollVersion() {
                        const active = document.activeElement;
                        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;

                        fetch('{{ route('order-desain.version') }}')
                            .then(r => r.json())
                            .then(data => {
                                if (data.version !== this.pageVersion) {
                                    this.rememberPosition();
                                    window.location.reload();
                                }
                            });
                    },
                    switchTab(key) {
                        this.tab = key;
                        this.selected = {};
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', key);
                        window.history.replaceState({}, '', url);
                    },
                    toggle(type, id, checked) {
                        const key = type + '-' + id;
                        if (checked) { this.selected[key] = { type, id }; } else { delete this.selected[key]; }
                    },
                    async bulkSend() {
                        const entries = Object.values(this.selected);
                        if (entries.length === 0) return;

                        for (const e of entries) {
                            const el = document.getElementById('qty-' + e.type + '-' + e.id);
                            if (!el.value || Number(el.value) < 1) {
                                alert('Isi qty untuk semua item yang dicentang dulu.');
                                el.focus();
                                return;
                            }
                        }

                        if (!confirm(`Kirim ${entries.length} item terpilih ke Cetak?`)) return;

                        this.sending = true;
                        try {
                            this.rememberPosition('layout-item-' + entries[0].type + '-' + entries[0].id);
                            for (const e of entries) {
                                const el = document.getElementById('qty-' + e.type + '-' + e.id);
                                await axios.post(`/order-desain/progress/${e.type}/${e.id}`, { qty: el.value });
                            }
                            window.location.reload();
                        } catch (err) {
                            alert('Gagal mengirim sebagian item. Muat ulang halaman lalu cek lagi.');
                            this.sending = false;
                        }
                    },
                 }">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-3);">
                    <div style="display: flex;">
                        @foreach ($tabs as $key => $t)
                            <button type="button" @click="switchTab('{{ $key }}')" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                                {{ $t['label'] }} ({{ $t['count'] }})
                            </button>
                        @endforeach
                    </div>
                    <button type="button" class="in-btn" :disabled="selectedCount === 0 || sending" @click="bulkSend()"
                            :style="(selectedCount === 0 || sending) ? 'opacity:0.5; cursor:not-allowed;' : ''">
                        <span x-text="sending ? 'Mengirim...' : 'Kirim Terpilih (' + selectedCount + ') ke Cetak'"></span>
                    </button>
                </div>

                {{-- Indoor: 1 card per order (order bisa muncul di sini dengan
                     sebagian baris item saja — baris lain mungkin sudah
                     pindah ke tahap Cetak) --}}
                @if (isset($tabs['indoor']))
                    <div x-show="tab === 'indoor'" style="margin-top: var(--space-4);">
                        @forelse ($indoorItems as $items)
                            @php $order = $items->first()->order; @endphp
                            <div class="order-card">
                                <x-order-date-rail :date="$order->TglOrder" />
                                <div class="order-card-head">
                                    <div class="order-summary">
                                        <div class="order-identity">
                                            <x-order-number :number="$order->NoOrder" />
                                            <x-macet-badge :show="$order->isMacet()" />
                                        </div>
                                        <div class="order-customer-line">
                                            <span class="order-meta-customer">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</span>
                                        </div>
                                    </div>
                                    <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                        <x-order-rework type="indoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                         current-stage="desain" :max-qty="$items->sum(fn ($i) => $i->qtyAt('desain'))"
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
                                            {{ $item->Judul }}
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
                        @empty
                            <div class="blueprint text-muted" style="padding: var(--space-6); text-align: center;">Tidak ada order di antrian desain.</div>
                        @endforelse
                    </div>
                @endif

                {{-- Outdoor: 1 card per order, per item input qty parsial --}}
                @if (isset($tabs['outdoor']))
                    <div x-show="tab === 'outdoor'" style="margin-top: var(--space-4);">
                        @forelse ($outdoorItems as $items)
                            @php $order = $items->first()->order; @endphp
                            <div class="order-card">
                                <x-order-date-rail :date="$order->TglOrder" />
                                <div class="order-card-head">
                                    <div class="order-summary">
                                        <div class="order-identity">
                                            <x-order-number :number="$order->NoOrder" />
                                            <x-macet-badge :show="$order->isMacet()" />
                                        </div>
                                        <div class="order-customer-line">
                                            <span class="order-meta-customer">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</span>
                                            <span class="order-meta-divider" aria-hidden="true"></span>
                                            <span class="order-meta-operator">Operator : {{ $order->createdBy?->name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                        <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                             :comments="$outdoorComments->get($order->id, collect())"
                                                             :unread="$outdoorUnread->get($order->id, 0)" :compact="true" />
                                        <x-order-rework type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                         current-stage="desain" :max-qty="$items->sum(fn ($i) => $i->qtyAt('desain'))"
                                                         :pending="$pendingRework->get('outdoor-'.$order->id)"
                                                         :can-approve="$canApproveRework" :compact="true" />
                                        @if ($order->cancel_requested_at)
                                            <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
                                            @can('order-outdoor.approve-cancel')
                                                <form method="POST" action="{{ route('order-outdoor.approve-cancel', $order) }}"
                                                      onsubmit="return confirm('Setujui pembatalan order {{ $order->NoOrder }} dengan nota pengganti? Nota lama akan dihanguskan.')">
                                                    @csrf
                                                    <input type="hidden" name="resolution" value="nota_pengganti">
                                                    <button type="submit" class="in-btn">Setujui + Nota Pengganti</button>
                                                </form>
                                                <form method="POST" action="{{ route('order-outdoor.approve-cancel', $order) }}"
                                                      onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $order->NoOrder }}? Tidak akan ada nota pengganti.')">
                                                    @csrf
                                                    <input type="hidden" name="resolution" value="batal_total">
                                                    <button type="submit" class="in-btn in-btn-danger">Setujui Batal Total</button>
                                                </form>
                                                <form method="POST" action="{{ route('order-outdoor.reject-cancel', $order) }}"
                                                      onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $order->NoOrder }}?')">
                                                    @csrf
                                                    <button type="submit" class="in-btn in-btn-ghost">Tolak</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </div>

                                @foreach ($items as $item)
                                    <div id="layout-item-outdoor-{{ $item->id }}" class="item-row">
                                        <div>
                                            <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                            <span class="item-meta-divider" aria-hidden="true"></span>
                                            <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                                {{ $bahanNames[$item->bahanCode()] ?? '-' }}
                                                @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                                    <span class="item-meta-divider item-meta-divider-small" aria-hidden="true"></span>
                                                    {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div style="display: inline-flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                                            @php
                                                $layoutRevision = $layoutRevisionItems->get($item->id);
                                                $isLayoutRevision = $layoutRevision !== null;
                                                $canEditNmFile = auth()->user()->hasPermission('order-desain.nmfile-manage');
                                            @endphp
                                            @if ($isLayoutRevision)
                                                <span class="tag tag-outline revision-source-tag"
                                                      style="border-color:#f59e0b; color:#b45309; background:#fffbeb; white-space:nowrap;"
                                                      title="{{ $layoutRevision->reason }}">
                                                    Revisi dari {{ \App\Models\OrderReworkRequest::STAGE_LABELS[$layoutRevision->current_stage] ?? ucfirst($layoutRevision->current_stage) }}
                                                </span>
                                            @endif
                                            @if ($canEditNmFile)
                                                <form method="POST" action="{{ route('order-desain.nmfile', $item) }}">
                                                    @csrf
                                                    <input type="text" name="NmFile" value="{{ $item->NmFile }}" maxlength="255"
                                                           placeholder="Nama file{{ $isLayoutRevision ? ' hasil revisi' : '' }}"
                                                           @change="rememberPosition('layout-item-outdoor-{{ $item->id }}'); $el.form.submit()" class="in-input"
                                                           style="width: {{ $isLayoutRevision ? '190px' : '140px' }}; {{ $isLayoutRevision ? 'border-color:#f59e0b; background:#fffbeb;' : '' }}">
                                                </form>
                                            @else
                                                <input type="text" value="{{ $item->NmFile }}" class="in-input" style="width: 140px;" disabled
                                                       title="Nama File hanya dapat dilihat oleh Operator Layout">
                                            @endif
                                            @can('order-desain.manage')
                                                <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                    @csrf
                                                    <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                           placeholder="Gabungan"
                                                           @change="rememberPosition('layout-item-outdoor-{{ $item->id }}'); $el.form.submit()" class="in-input" style="width: 140px;">
                                                </form>
                                            @else
                                                <span class="text-muted" style="white-space: nowrap;">{{ $item->gabungan ?: '-' }}</span>
                                            @endcan
                                            <span class="progress-tag">Progres Desain: {{ $item->Qty - $item->qtyAt('desain') }}/{{ $item->Qty }}</span>
                                            @can('order-desain.manage')
                                                <input type="checkbox" @change="toggle('outdoor', {{ $item->id }}, $event.target.checked)" title="Pilih untuk kirim massal">
                                                <form method="POST" action="{{ route('order-desain.progress', ['outdoor', $item->id]) }}"
                                                      @submit="rememberPosition('layout-item-outdoor-{{ $item->id }}')"
                                                      style="display: flex; align-items: center; gap: 4px;">
                                                    @csrf
                                                    <input type="number" id="qty-outdoor-{{ $item->id }}" name="qty" min="1" max="{{ $item->qtyAt('desain') }}" placeholder="qty" required
                                                           oninput="this.setCustomValidity('')"
                                                           oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Isi jumlah qty dulu.' : (this.validity.rangeOverflow ? 'Maksimal {{ $item->qtyAt('desain') }} (sisa di Desain).' : (this.validity.rangeUnderflow ? 'Qty minimal 1.' : 'Qty tidak valid.')))"
                                                           class="in-input no-spinner" style="width: 70px;">
                                                    <button type="submit" class="in-btn">Kirim ke Cetak</button>
                                                </form>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                        @endforelse

                        {{-- Order yang sudah lewat tahap desain, tapi Status Cetak baru saja
                             membalas diskusinya — muncul lagi supaya desain bisa membalas. --}}
                        @foreach ($outdoorNeedsReply as $order)
                            <div class="order-card" style="background: color-mix(in srgb, var(--color-accent) 6%, transparent);">
                                <x-order-date-rail :date="$order->TglOrder" />
                                <div class="order-card-head">
                                    <div class="order-summary">
                                        <div class="order-identity">
                                            <x-order-number :number="$order->NoOrder" />
                                            <x-macet-badge :show="$order->isMacet()" />
                                        </div>
                                        <div class="order-customer-line">
                                            <span class="order-meta-customer">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                        <span class="tag tag-outline">Balasan baru &middot; status: {{ ucfirst(str_replace('_', ' ', $order->status ?? '-')) }}</span>
                                        <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                             :comments="$outdoorComments->get($order->id, collect())"
                                                             :unread="$outdoorUnread->get($order->id, 0)" />
                                    </div>
                                </div>
                                @foreach ($order->items as $item)
                                    <div class="item-row">
                                        <div>
                                            <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                            <span class="item-meta-divider" aria-hidden="true"></span>
                                            <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">File: {{ $item->NmFile ?: '-' }}</span>
                                        </div>
                                        <span class="text-muted">
                                            @if ($item->qtyAt('desain') > 0)
                                                {{ $item->qtyAt('desain') }}/{{ $item->Qty }} masih di Desain
                                            @else
                                                <span class="tag tag-accent">Sudah lanjut ke Cetak</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        @if ($outdoorItems->isEmpty() && $outdoorNeedsReply->isEmpty())
                            <div class="blueprint text-muted" style="padding: var(--space-6); text-align: center;">Tidak ada order di antrian desain.</div>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
