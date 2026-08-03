<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Cetak</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-cetak { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-cetak .seg-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-family: var(--font-heading); font-weight: 600; font-size: 13px; letter-spacing: 0.02em; cursor: pointer; border: 1px solid var(--color-divider); border-right: none; background: transparent; color: var(--color-text); }
            #industry-cetak .seg-tab:last-child { border-right: 1px solid var(--color-divider); }
            #industry-cetak .seg-tab.active { background: var(--color-accent); color: var(--color-bg); border-color: var(--color-accent); }
            #industry-cetak .in-input { width: 64px; min-height: 30px; padding: 4px 8px; font: inherit; font-size: 13px; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-divider); }
            #industry-cetak .in-btn { display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-heading); font-weight: 600; font-size: 12px; padding: 5px 10px; background: var(--color-accent); color: var(--color-bg); border: 1px solid var(--color-accent); cursor: pointer; }
            #industry-cetak .in-btn:hover { background: var(--color-accent-600); }
        </style>
    @endpush

    @php
        $tabs = [
            'indoor' => ['label' => 'Indoor', 'count' => $indoorOrders->count()],
            'outdoor' => ['label' => 'Outdoor', 'count' => $outdoorOrders->count()],
            'artwork' => ['label' => 'Artwork', 'count' => $artworkOrders->count()],
        ];
        $initialTab = in_array(request('tab'), ['indoor', 'outdoor', 'artwork']) ? request('tab') : 'indoor';
    @endphp

    <div id="industry-cetak">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);"
             x-data="{ modalOpen: false, type: '', id: null, noOrder: '' }">

            <div x-data="{ tab: '{{ $initialTab }}' }">
                <div style="display: flex;">
                    @foreach ($tabs as $key => $t)
                        <button type="button" @click="tab = '{{ $key }}'" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                            {{ $t['label'] }} ({{ $t['count'] }})
                        </button>
                    @endforeach
                </div>

                @foreach (['indoor' => $indoorOrders, 'artwork' => $artworkOrders] as $tabKey => $orders)
                    <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif
                         class="blueprint" style="padding: var(--space-6); margin-top: var(--space-4);">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                        <div style="overflow-x: auto;">
                            <table class="table" style="min-width: 560px;">
                                <thead>
                                    <tr>
                                        <th style="width: 32px;">No</th><th>No order</th><th>Tanggal</th><th>Customer</th><th style="text-align: right;">Aksi</th>
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
                                            <td class="text-muted">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                                            <td>{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-rework :type="$tabKey" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                     current-stage="cetak"
                                                                     :pending="$pendingRework->get($tabKey.'-'.$order->id)"
                                                                     :can-approve="$canApproveRework" />
                                                    @if (! $pendingRework->has($tabKey.'-'.$order->id))
                                                        @can('order-cetak.manage')
                                                            <button type="button" class="in-btn"
                                                                    @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $order->id }}; noOrder = '{{ $order->NoOrder }}'">
                                                                Kirim Finishing
                                                            </button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order di antrian cetak.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Outdoor: dikerjakan bertahap per item, qty diakumulasi sampai sesuai pesanan --}}
                <div x-show="tab === 'outdoor'" x-cloak class="blueprint" style="padding: var(--space-6); margin-top: var(--space-4);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div style="overflow-x: auto;">
                        <table class="table" style="min-width: 680px;">
                            <thead>
                                <tr>
                                    <th style="width: 32px;">No</th><th>No order</th><th>Printer</th><th>Gabungan</th><th>Qty</th><th>Progress</th><th>Customer</th><th style="text-align: right;">Diskusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @forelse ($outdoorOrders as $order)
                                    @if ($order->cancel_requested_at)
                                        @php $rowNum++; @endphp
                                        <tr>
                                            <td class="text-muted">{{ $rowNum }}</td>
                                            <td style="font-family: var(--font-heading); font-weight: 600;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-number :number="$order->NoOrder" />
                                                    <x-macet-badge :show="$order->isMacet()" />
                                                </div>
                                            </td>
                                            <td colspan="4">
                                                <span class="tag tag-danger" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Batal</span>
                                            </td>
                                            <td>{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                            <td style="text-align: right;">
                                                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                     :comments="$outdoorComments->get($order->id, collect())"
                                                                     :unread="$outdoorUnread->get($order->id, 0)" />
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($order->items as $item)
                                            @php
                                                $rowNum++;
                                                $isLastPending = $order->items->where('id', '!=', $item->id)->every->isSelesai();
                                            @endphp
                                            <tr>
                                                <td class="text-muted">{{ $rowNum }}</td>
                                                <td style="font-family: var(--font-heading); font-weight: 600;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <x-order-number :number="$order->NoOrder" />
                                                    <x-macet-badge :show="$order->isMacet()" />
                                                </div>
                                            </td>
                                                <td style="white-space: nowrap;">
                                                    <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                                </td>
                                                <td class="text-muted" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item->gabungan }}">{{ $item->gabungan ?: '-' }}</td>
                                                <td class="text-muted" style="white-space: nowrap;">{{ $item->qty_diproses }}/{{ $item->Qty }}</td>
                                                <td>
                                                    @if ($item->isSelesai())
                                                        <span class="tag tag-accent">Selesai</span>
                                                    @elsecan('order-cetak.manage')
                                                        <form method="POST" action="{{ route('order-cetak.progress', $item) }}" style="display: flex; align-items: center; gap: 4px;"
                                                              x-data="{ qty: '' }"
                                                              @submit="if ({{ $isLastPending ? 'true' : 'false' }} && Number(qty) >= {{ $item->sisaQty() }} && !confirm('Order {{ $order->NoOrder }} akan tuntas dan pindah ke antrian Finishing. Yakin?')) { $event.preventDefault(); }">
                                                            @csrf
                                                            <input type="number" name="qty" x-model="qty" min="1" max="{{ $item->sisaQty() }}" placeholder="qty" required
                                                                   oninput="this.setCustomValidity('')"
                                                                   oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Isi jumlah qty dulu.' : (this.validity.rangeOverflow ? 'Maksimal {{ $item->sisaQty() }} (sisa qty pesanan).' : (this.validity.rangeUnderflow ? 'Qty minimal 1.' : 'Qty tidak valid.')))"
                                                                   class="in-input no-spinner">
                                                            <button type="submit" class="in-btn">Tambah</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">Belum selesai</span>
                                                    @endif
                                                </td>
                                                <td>{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                                <td style="text-align: right;">
                                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                        <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                             :comments="$outdoorComments->get($order->id, collect())"
                                                                             :unread="$outdoorUnread->get($order->id, 0)" />
                                                        <x-order-rework type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                         current-stage="cetak"
                                                                         :pending="$pendingRework->get('outdoor-'.$order->id)"
                                                                         :can-approve="$canApproveRework" />
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach

                                        @can('order-cetak.manage')
                                        @if ($order->items->every->isSelesai() && ! $pendingRework->has('outdoor-'.$order->id))
                                            <tr>
                                                <td colspan="5"></td>
                                                <td colspan="2">
                                                    <form method="POST" action="{{ route('order-cetak.finish-outdoor', $order) }}"
                                                          onsubmit="return confirm('Order {{ $order->NoOrder }} sudah tuntas, pindahkan ke antrian Finishing?')">
                                                        @csrf
                                                        <button type="submit" class="in-btn">Kirim Finishing</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endif
                                        @endcan
                                    @endif
                                @empty
                                    <tr><td colspan="8" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order di antrian cetak.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" class="dialog-backdrop">
                <div @click="modalOpen = false" style="position: absolute; inset: 0;"></div>
                <div class="dialog blueprint" style="position: relative;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <form method="POST" :action="`/order-cetak/${type}/${id}`" style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @csrf
                        <div class="dialog-title">Update status — <span x-text="noOrder"></span></div>

                        <div class="field">
                            <label>Status</label>
                            <select name="action" class="input">
                                <option value="selesai">Selesai</option>
                                <option value="lanjut">Lanjut</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Catatan (opsional)</label>
                            <textarea name="catatan" rows="2" class="input"></textarea>
                        </div>

                        <div class="dialog-actions">
                            <button type="button" @click="modalOpen = false" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
