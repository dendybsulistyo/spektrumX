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
])

<div class="order-card">
    <div class="order-card-head">
        <div style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-heading); font-weight: 700; font-size: 16px;">
            <x-order-number :number="$order->NoOrder" />
            <x-macet-badge :show="$order->isMacet()" />
            <span style="font-weight: 400; font-size: 16px; line-height: 1.6; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                {{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('d-m-y') }}
                &middot; {{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}
            </span>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
            @if ($showInvoiceLink)
                <a href="{{ route('invoice.show', ['type' => $type, 'id' => $order->id, 'source' => 'pengambilan']) }}"
                   class="tag tag-outline" title="Cek Nota Pemesanan">
                    Nota Pemesanan
                </a>
            @endif
            @if ($type === 'outdoor' && $outdoorComments !== null)
                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                     :comments="$outdoorComments->get($order->id, collect())"
                                     :unread="$outdoorUnread->get($order->id, 0)" :compact="true" />
            @endif
            <x-order-rework :type="$type" :order-id="$order->id" :no-order="$order->NoOrder"
                             :current-stage="$stage" :max-qty="$items->sum(fn ($i) => $i->qtyAt($stage))"
                             :pending="$pendingRework->get($type.'-'.$order->id)"
                             :can-approve="$canApproveRework" :compact="true" />
            @if ($order->cancel_requested_at)
                <span class="tag tag-outline" title="{{ $order->cancel_reason }}">Menunggu Persetujuan Pembatalan</span>
            @endif
        </div>
    </div>

    @foreach ($items as $item)
        <div class="item-row">
            <div>
                @if ($type === 'outdoor')
                    <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                    <span style="font-size: 14px; color: color-mix(in srgb, var(--color-text) 82%, transparent);">
                        {{ $item->gabungan ?: ($item->NmFile ?: '-') }}
                    </span>
                @else
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
                    @if ($capturePenerima)
                        {{-- Pengambilan butuh nama & kontak penerima dulu sebelum
                             diserahkan — tangkap lewat modal di halaman induk. --}}
                        <button type="button" class="in-btn"
                                @click="$dispatch('open-penerima-modal', { type: '{{ $type }}', id: {{ $item->id }}, qty: {{ $item->qtyAt($stage) }}, noOrder: '{{ $order->NoOrder }}' })">
                            Kirim {{ $nextLabel }}
                        </button>
                    @else
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
