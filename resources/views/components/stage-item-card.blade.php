@props([
    'type',
    'order',
    'items',
    'stage',
    'stageLabel',
    'routeName',
    'nextLabel',
    'pendingRework',
    'canApproveRework',
    'printerNames' => null,
    'outdoorComments' => null,
    'outdoorUnread' => null,
    'manageAbility',
    'capturePenerima' => false,
    'showInvoiceLink' => false,
    'indoorGroupBy' => 'order',
])

@php
    $pickupItems = $capturePenerima
        ? $items->map(fn ($item) => [
            'id' => $item->id,
            'label' => $type === 'outdoor'
                ? ($item->gabungan ?: ($item->NmFile ?: 'Item'))
                : ($item->Judul ?: 'Item'),
            'qty' => $item->qtyAt($stage),
            'max' => $item->qtyAt($stage),
        ])->values()
        : collect();
    $canPickup = ! $capturePenerima
        || ($order->status_bayar === 'lunas' && (float) $order->jumlah_piutang <= 0)
        || ($order->status_bayar === 'hutang' && $order->customer?->isVip);
@endphp

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
            @if ($showInvoiceLink)
                <a href="{{ route('invoice.show', ['type' => $type, 'id' => $order->id, 'source' => 'pengambilan', 'draft' => 1]) }}"
                   class="tag tag-outline" title="Lihat Draft Nota">
                    Draft Nota
                </a>
            @endif
            @if ($capturePenerima && ! $canPickup)
                <span class="tag tag-outline">Belum lunas — proses di Kasir</span>
            @endif
            @if ($capturePenerima && $canPickup && $type === 'indoor')
                @can($manageAbility)
                    <button type="button" class="in-btn"
                            @click="$dispatch('open-penerima-modal', { type: 'indoor', id: {{ $pickupItems->first()['id'] }}, noOrder: '{{ $order->NoOrder }}', items: {{ Illuminate\Support\Js::from($pickupItems) }} })">
                        Serahkan Pesanan
                    </button>
                @endcan
            @endif
            @if ($type === 'outdoor' && $outdoorComments !== null)
                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                     :comments="$outdoorComments->get($order->id, collect())"
                                     :unread="$outdoorUnread->get($order->id, 0)" :compact="true" />
            @endif
            <x-order-rework :type="$type" :order-id="$order->id" :no-order="$order->NoOrder"
                             :current-stage="$stage" :max-qty="$items->sum(fn ($i) => $i->qtyAt($stage))"
                             :items="$items"
                             :pending="$pendingRework->get($type.'-'.$order->id)"
                             :can-approve="$canApproveRework" :compact="true" />
            @if ($order->cancel_requested_at)
                <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
            @endif
        </div>
    </div>

    @foreach ($items as $item)
        @php
            $layoutRevisionCompletion = $type === 'outdoor'
                ? $item->layoutRevisionCompletions->first()
                : null;
        @endphp
        <div class="item-row">
            <div>
                @if ($type === 'outdoor')
                    <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                    <span class="item-meta-divider" aria-hidden="true"></span>
                    <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                        @if (filled($item->gabungan))
                            {{ $item->gabungan }}
                        @endif
                        @if (filled($item->NmFile))
                            @if (filled($item->gabungan))
                                <span class="item-meta-divider item-meta-divider-small" aria-hidden="true"></span>
                            @endif
                            <span style="color:var(--color-text);">{{ $item->NmFile }}</span>
                        @endif
                        @if (blank($item->gabungan) && blank($item->NmFile)) - @endif
                    </span>
                    @if ($layoutRevisionCompletion)
                        <span class="tag"
                              style="margin-left:8px; border:1px solid #86efac; background:#ecfdf5; color:#047857; white-space:nowrap;"
                              title="{{ $layoutRevisionCompletion->catatan }}">
                            Revisi Layout selesai &middot; {{ $layoutRevisionCompletion->user?->name ?? 'Operator Layout' }}
                        </span>
                    @endif
                @else
                    @if ($indoorGroupBy !== 'product')
                        <strong>{{ $item->NmProd ?: ($item->produk?->NmProd ?? 'Produk tanpa nama') }}</strong>
                        @if ($item->Judul)
                            <span class="item-meta-divider" aria-hidden="true"></span>
                        @endif
                    @endif
                    {{ $item->Judul }}
                    @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                        <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                            ({{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }} cm)
                        </span>
                    @endif
                @endif
            </div>
            <div style="display: inline-flex; align-items: center; gap: var(--space-3);">
                @if ($capturePenerima)
                    {{-- Pengambilan perlu menampilkan Qty yang benar-benar
                         tersedia untuk diserahkan, bukan Qty yang sudah
                         keluar dari tahap Siap Diambil. --}}
                    <span class="progress-tag">Siap Diserahkan: {{ $item->qtyAt($stage) }}/{{ $item->Qty }}</span>
                @else
                    {{-- Qty yang SUDAH dikirim maju dari tahap ini (0 di awal,
                         naik seiring diproses) — bukan qty yang masih tersisa. --}}
                    <span class="progress-tag">Progres di {{ $stageLabel }}: {{ $item->Qty - $item->qtyAt($stage) }}/{{ $item->Qty }}</span>
                @endif
                @can($manageAbility)
                    @if ($capturePenerima && $canPickup && $type !== 'indoor')
                        {{-- Pengambilan butuh nama & kontak penerima dulu sebelum
                             diserahkan — tangkap lewat modal di halaman induk. --}}
                        <button type="button" class="in-btn"
                                @click="$dispatch('open-penerima-modal', { type: '{{ $type }}', id: {{ $item->id }}, noOrder: '{{ $order->NoOrder }}', items: {{ Illuminate\Support\Js::from($pickupItems->where('id', $item->id)->values()) }} })">
                            Kirim {{ $nextLabel }}
                        </button>
                    @elseif (! $capturePenerima)
                        {{-- Operator boleh meneruskan sebagian Qty. Batas di browser
                             dan server sama-sama memakai sisa Qty di tahap ini. --}}
                        <form method="POST" action="{{ route($routeName, [$type, $item->id]) }}" style="display: flex; align-items: center; gap: 4px;">
                            @csrf
                            <input type="number" name="qty" min="1" max="{{ $item->qtyAt($stage) }}" value="{{ $item->qtyAt($stage) }}" required
                                   oninput="this.setCustomValidity('')"
                                   oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Isi jumlah Qty dulu.' : (this.validity.rangeOverflow ? 'Maksimal {{ $item->qtyAt($stage) }} sesuai sisa Qty di {{ $stageLabel }}.' : (this.validity.rangeUnderflow ? 'Qty minimal 1.' : 'Qty tidak valid.')))"
                                   class="in-input no-spinner" style="width: 70px;">
                            <button type="submit" class="in-btn">Kirim {{ $nextLabel }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    @endforeach
</div>
