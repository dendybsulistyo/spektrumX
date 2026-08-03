<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Desain</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-desain { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-desain .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-desain .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-desain .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-desain .in-input { width: 100%; min-height: 28px; padding: 4px 6px; font: inherit; font-size: 12px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-desain .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 12px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; white-space: nowrap; }
            #industry-desain .in-btn:hover { background: var(--color-accent-600); }
            #industry-desain .in-btn-danger { background: var(--color-accent-900); border-color: var(--color-accent-900); }
            #industry-desain .in-btn-danger:hover { background: var(--color-accent-800); }
            #industry-desain .in-btn-ghost { background: transparent; color: var(--color-text); border-color: var(--color-divider); }
        </style>
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorOrders->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorOrders->count() + $outdoorNeedsReply->count()],
            'artwork' => ['label' => 'Artwork', 'count' => $artworkOrders->count()],
        ];
        $initialTab = in_array(request('tab'), ['indoor', 'outdoor', 'artwork']) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-desain">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);"
             x-data="{ modalOpen: false, type: '', id: null, noOrder: '', cancelModalOpen: false, cancelId: null, cancelNoOrder: '' }">

            <div x-data="{ tab: '{{ $initialTab }}' }">
                <div style="display: flex;">
                    @foreach ($tabs as $key => $t)
                        <button type="button" @click="tab = '{{ $key }}'" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                            {{ $t['label'] }} ({{ $t['count'] }})
                        </button>
                    @endforeach
                </div>

                {{-- Indoor & Artwork: pecah per unit qty --}}
                @foreach (['indoor' => $indoorRows, 'artwork' => $artworkRows] as $tabKey => $rows)
                    <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif
                         class="blueprint" style="padding: var(--space-6); margin-top: var(--space-4);">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                        <div style="overflow-x: auto;">
                            <table class="table" style="min-width: 640px;">
                                <thead>
                                    <tr>
                                        <th style="width: 32px;">No</th><th>No order</th><th>Item</th><th style="width: 90px;">Qty</th><th>Tanggal</th><th>Customer</th><th style="text-align: right;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td class="text-muted">{{ $loop->iteration }}</td>
                                            <td style="font-family: var(--font-heading); font-weight: 600;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-number :number="$row->order->NoOrder" />
                                                    <x-macet-badge :show="$row->order->isMacet()" />
                                                </div>
                                            </td>
                                            <td>
                                                {{ $row->itemName }}
                                                @if ($row->size)
                                                    <span class="text-muted" style="font-size: 11px;">({{ $row->size }})</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">
                                                @if ($row->unitIndex === null)
                                                    Qty {{ $row->unitTotal }}
                                                @else
                                                    {{ $row->unitIndex }}/{{ $row->unitTotal }}
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ is_string($row->order->TglOrder) ? $row->order->TglOrder : $row->order->TglOrder?->format('Y-m-d') }}</td>
                                            <td>{{ $row->order->customer?->NmCust ? ucwords(mb_strtolower($row->order->customer->NmCust)) : '-' }}</td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-rework :type="$tabKey" :order-id="$row->order->id" :no-order="$row->order->NoOrder"
                                                                     current-stage="desain"
                                                                     :pending="$pendingRework->get($tabKey.'-'.$row->order->id)"
                                                                     :can-approve="$canApproveRework" />
                                                    @unless ($pendingRework->has($tabKey.'-'.$row->order->id))
                                                        <button type="button" class="in-btn"
                                                                @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $row->order->id }}; noOrder = '{{ $row->order->NoOrder }}'">
                                                            Update status
                                                        </button>
                                                    @endunless
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order di antrian desain.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Outdoor: 1 baris per item --}}
                <div x-show="tab === 'outdoor'" x-cloak class="blueprint" style="padding: var(--space-6); margin-top: var(--space-4);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div style="overflow-x: auto;">
                        <table class="table" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="width: 32px;">No</th><th>No order</th><th>Customer</th><th>Penerima</th><th>Printer</th><th>Bahan</th><th>Ukuran</th><th>Qty</th><th>File</th><th style="width: 120px;">Gabungan</th><th>Tgl</th><th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse ($outdoorOrders as $order)
                                    @foreach ($order->items as $item)
                                        @php $rowNum++; @endphp
                                        <tr>
                                            <td class="text-muted">{{ $rowNum }}</td>
                                            <td style="font-family: var(--font-heading); font-weight: 600;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-number :number="$order->NoOrder" />
                                                    <x-macet-badge :show="$order->isMacet()" />
                                                </div>
                                            </td>
                                            <td class="text-muted" style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $order->customer?->NmCust }}">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                            <td class="text-muted" style="max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $order->createdBy?->name }}">{{ $order->createdBy?->name ?? '-' }}</td>
                                            <td style="white-space: nowrap;">
                                                <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                            </td>
                                            <td class="text-muted" style="white-space: nowrap;">{{ $bahanNames[$item->bahanCode()] ?? '-' }}</td>
                                            <td class="text-muted" style="white-space: nowrap;">
                                                @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                                    {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $item->Qty }}</td>
                                            <td class="text-muted" style="white-space: nowrap; min-width: 110px;">{{ $item->NmFile ?: '-' }}</td>
                                            <td>
                                                @can('order-desain.manage')
                                                    <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                        @csrf
                                                        <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                               placeholder="-" onchange="this.form.submit()" class="in-input">
                                                    </form>
                                                @else
                                                    <span class="text-muted" style="white-space: nowrap;">{{ $item->gabungan ?: '-' }}</span>
                                                @endcan
                                            </td>
                                            <td class="text-muted" style="white-space: nowrap;">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}</td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: flex-end;">
                                                    <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                         :comments="$outdoorComments->get($order->id, collect())"
                                                                         :unread="$outdoorUnread->get($order->id, 0)" />
                                                    <x-order-rework type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                     current-stage="desain"
                                                                     :pending="$pendingRework->get('outdoor-'.$order->id)"
                                                                     :can-approve="$canApproveRework" />
                                                    @if ($order->cancel_requested_at)
                                                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; width: 100%;">
                                                            <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
                                                            <span class="text-muted" style="font-size: 10px;">diajukan {{ $order->cancelRequestedBy?->name ?? '-' }}</span>
                                                            @can('order-outdoor.approve-cancel')
                                                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; margin-top: 2px;">
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
                                                                </div>
                                                            @endcan
                                                        </div>
                                                    @elseif (! $pendingRework->has('outdoor-'.$order->id))
                                                        @can('order-desain.manage')
                                                            <form method="POST" action="{{ route('order-desain.update', ['outdoor', $order->id]) }}"
                                                                  onsubmit="return confirm('Yakin ingin mengirim order {{ $order->NoOrder }} ke Cetak?')">
                                                                @csrf
                                                                <input type="hidden" name="action" value="selesai">
                                                                <button type="submit" class="in-btn">Kirim Cetak</button>
                                                            </form>
                                                            <button type="button" class="in-btn in-btn-danger"
                                                                    @click="cancelModalOpen = true; cancelId = {{ $order->id }}; cancelNoOrder = '{{ $order->NoOrder }}'">
                                                                Batal &amp; IB
                                                            </button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                @endforelse

                                {{-- Order yang sudah lewat tahap desain, tapi Status Cetak baru saja
                                     membalas diskusinya — muncul lagi supaya desain bisa membalas. --}}
                                @foreach ($outdoorNeedsReply as $order)
                                    @foreach ($order->items as $item)
                                        @php $rowNum++; @endphp
                                        <tr style="background: color-mix(in srgb, var(--color-accent) 6%, transparent);">
                                            <td class="text-muted">{{ $rowNum }}</td>
                                            <td style="font-family: var(--font-heading); font-weight: 600;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-number :number="$order->NoOrder" />
                                                    <x-macet-badge :show="$order->isMacet()" />
                                                </div>
                                            </td>
                                            <td class="text-muted" style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $order->customer?->NmCust }}">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                            <td class="text-muted" style="max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $order->createdBy?->name }}">{{ $order->createdBy?->name ?? '-' }}</td>
                                            <td style="white-space: nowrap;">
                                                <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                            </td>
                                            <td class="text-muted" style="white-space: nowrap;">{{ $bahanNames[$item->bahanCode()] ?? '-' }}</td>
                                            <td class="text-muted" style="white-space: nowrap;">
                                                @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                                    {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $item->Qty }}</td>
                                            <td class="text-muted" style="white-space: nowrap;">{{ $item->NmFile ?: '-' }}</td>
                                            <td>
                                                @can('order-desain.manage')
                                                    <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                        @csrf
                                                        <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                               placeholder="-" onchange="this.form.submit()" class="in-input">
                                                    </form>
                                                @else
                                                    <span class="text-muted" style="white-space: nowrap;">{{ $item->gabungan ?: '-' }}</span>
                                                @endcan
                                            </td>
                                            <td class="text-muted" style="white-space: nowrap;">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}</td>
                                            <td style="text-align: right;">
                                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                                    <span class="tag tag-outline">Balasan baru &middot; status: {{ ucfirst(str_replace('_', ' ', $order->status ?? '-')) }}</span>
                                                    <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                         :comments="$outdoorComments->get($order->id, collect())"
                                                                         :unread="$outdoorUnread->get($order->id, 0)" />
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach

                                @if ($outdoorOrders->isEmpty() && $outdoorNeedsReply->isEmpty())
                                    <tr><td colspan="12" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order di antrian desain.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" class="dialog-backdrop">
                <div @click="modalOpen = false" style="position: absolute; inset: 0;"></div>
                <div class="dialog blueprint" style="position: relative;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <form method="POST" :action="`/order-desain/${type}/${id}`" style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @csrf
                        <div class="dialog-title">Update status — <span x-text="noOrder"></span></div>
                        <div class="field">
                            <label>Status</label>
                            <select name="action" class="input">
                                <option value="selesai">Selesai</option>
                                <option value="lanjut">Lanjut</option>
                            </select>
                        </div>
                        <div class="dialog-actions">
                            <button type="button" @click="modalOpen = false" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="cancelModalOpen" x-cloak @keydown.escape.window="cancelModalOpen = false" class="dialog-backdrop">
                <div @click="cancelModalOpen = false" style="position: absolute; inset: 0;"></div>
                <div class="dialog blueprint" style="position: relative;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <form method="POST" :action="`/order-outdoor/${cancelId}/request-cancel`" style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @csrf
                        <div class="dialog-title">Ajukan pembatalan — <span x-text="cancelNoOrder"></span></div>
                        <p class="text-muted" style="font-size: 12px; margin: 0;">Order akan ditandai menunggu persetujuan. Perlu disetujui Admin/Admin Kasir sebelum benar-benar dibatalkan.</p>
                        <div class="field">
                            <label>Alasan pembatalan</label>
                            <textarea name="cancel_reason" rows="3" required maxlength="255" class="input" placeholder="misal: customer minta batal, salah spesifikasi, dll"></textarea>
                        </div>
                        <div class="dialog-actions">
                            <button type="button" @click="cancelModalOpen = false" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-primary">Ajukan pembatalan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
