@props([
    'type',
    'itemGroups',
    'groupBy',
    'stage',
    'stageLabel',
    'routeName',
    'nextLabel',
    'pendingRework',
    'canApproveRework',
    'printerNames',
    'outdoorComments',
    'outdoorUnread',
    'manageAbility',
    'emptyMessage',
    'capturePenerima' => false,
    'showInvoiceLink' => false,
])

@php
    $groupedItems = $type === 'indoor' && $groupBy !== 'order'
        ? $itemGroups->flatten(1)
            ->groupBy($groupBy === 'division'
                ? fn ($item) => $item->produk?->kategori?->NmDivs ?: 'Tanpa Divisi'
                : fn ($item) => $item->NmProd ?: ($item->produk?->NmProd ?: 'Tanpa Produk'))
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
        : null;
@endphp

@if ($itemGroups->isEmpty())
    <div class="blueprint text-muted" style="padding:var(--space-6);text-align:center;">{{ $emptyMessage }}</div>
@elseif ($groupedItems !== null)
    @foreach ($groupedItems as $groupName => $groupItems)
        <section>
            <div class="operator-group-heading">
                <span class="operator-group-heading-title">{{ $groupName }}</span>
                <span class="operator-group-heading-count">{{ $groupItems->count() }} item &middot; {{ $groupItems->sum(fn ($item) => $item->qtyAt($stage)) }} qty tersisa</span>
            </div>
            @foreach ($groupItems->groupBy('order_indoor_id') as $items)
                <x-stage-item-card :type="$type" :order="$items->first()->order" :items="$items"
                                   :stage="$stage" :stage-label="$stageLabel" :route-name="$routeName" :next-label="$nextLabel"
                                   :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                                   :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                                   :manage-ability="$manageAbility" :capture-penerima="$capturePenerima"
                                   :show-invoice-link="$showInvoiceLink" :indoor-group-by="$groupBy" />
            @endforeach
        </section>
    @endforeach
@else
    @foreach ($itemGroups as $items)
        <x-stage-item-card :type="$type" :order="$items->first()->order" :items="$items"
                           :stage="$stage" :stage-label="$stageLabel" :route-name="$routeName" :next-label="$nextLabel"
                           :pending-rework="$pendingRework" :can-approve-rework="$canApproveRework"
                           :printer-names="$printerNames" :outdoor-comments="$outdoorComments" :outdoor-unread="$outdoorUnread"
                           :manage-ability="$manageAbility" :capture-penerima="$capturePenerima"
                           :show-invoice-link="$showInvoiceLink" :indoor-group-by="$groupBy" />
    @endforeach
@endif
