<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Operator Layout / Desain</h2>
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
        </style>
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
                    get selectedCount() { return Object.keys(this.selected).length; },
                    switchTab(key) { this.tab = key; this.selected = {}; },
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
                                <div class="order-card-head">
                                    <div style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-heading); font-weight: 700; font-size: 16px;">
                                        <x-order-number :number="$order->NoOrder" />
                                        <x-macet-badge :show="$order->isMacet()" />
                                        <span style="font-weight: 400; font-size: 16px; line-height: 1.6; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                            {{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}
                                            &middot; {{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}
                                        </span>
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
                                    <div class="item-row">
                                        <div>
                                            {{ $item->Judul }}
                                            @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                                <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                                    ({{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }} cm)
                                                </span>
                                            @endif
                                        </div>
                                        <div style="display: inline-flex; align-items: center; gap: var(--space-3);">
                                            <span class="progress-tag">Progres di Desain: {{ $item->Qty - $item->qtyAt('desain') }}/{{ $item->Qty }}</span>
                                            @can('order-desain.manage')
                                                <input type="checkbox" @change="toggle('indoor', {{ $item->id }}, $event.target.checked)" title="Pilih untuk kirim massal">
                                                <form method="POST" action="{{ route('order-desain.progress', ['indoor', $item->id]) }}" style="display: flex; align-items: center; gap: 4px;">
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
                                <div class="order-card-head">
                                    <div style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-heading); font-weight: 700; font-size: 16px;">
                                        <x-order-number :number="$order->NoOrder" />
                                        <x-macet-badge :show="$order->isMacet()" />
                                        <span style="font-weight: 400; font-size: 16px; line-height: 1.6; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                            {{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}
                                            &middot; {{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}
                                            &middot; {{ $order->createdBy?->name ?? '-' }}
                                        </span>
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
                                    <div class="item-row">
                                        <div>
                                            <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                            <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                                {{ $bahanNames[$item->bahanCode()] ?? '-' }}
                                                @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                                    &middot; {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div style="display: inline-flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                                            @can('order-desain.nmfile-manage')
                                                <form method="POST" action="{{ route('order-desain.nmfile', $item) }}">
                                                    @csrf
                                                    <input type="text" name="NmFile" value="{{ $item->NmFile }}" maxlength="255"
                                                           placeholder="Nama file" onchange="this.form.submit()" class="in-input" style="width: 140px;">
                                                </form>
                                            @else
                                                <span class="text-muted" style="white-space: nowrap;" title="Hanya Operator File yang bisa ubah nama file">{{ $item->NmFile ?: '-' }}</span>
                                            @endcan
                                            @can('order-desain.manage')
                                                <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                    @csrf
                                                    <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                           placeholder="Gabungan" onchange="this.form.submit()" class="in-input" style="width: 140px;">
                                                </form>
                                            @else
                                                <span class="text-muted" style="white-space: nowrap;">{{ $item->gabungan ?: '-' }}</span>
                                            @endcan
                                            <span class="progress-tag">Progres di Desain: {{ $item->Qty - $item->qtyAt('desain') }}/{{ $item->Qty }}</span>
                                            @can('order-desain.manage')
                                                <input type="checkbox" @change="toggle('outdoor', {{ $item->id }}, $event.target.checked)" title="Pilih untuk kirim massal">
                                                <form method="POST" action="{{ route('order-desain.progress', ['outdoor', $item->id]) }}" style="display: flex; align-items: center; gap: 4px;">
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
                                <div class="order-card-head">
                                    <div style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-heading); font-weight: 700; font-size: 16px;">
                                        <x-order-number :number="$order->NoOrder" />
                                        <x-macet-badge :show="$order->isMacet()" />
                                        <span style="font-weight: 400; font-size: 16px; line-height: 1.6; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                                            {{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}
                                            &middot; {{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}
                                        </span>
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
