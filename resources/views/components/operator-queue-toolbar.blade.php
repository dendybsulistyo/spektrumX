@props([
    'tabs',
    'routeName',
    'groupBy',
    'query' => [],
])

<div class="operator-queue-controls">
    <div class="operator-queue-controls-start">
        <div style="display:flex;">
            @foreach ($tabs as $key => $tab)
                <button type="button" @click="setTab('{{ $key }}')" class="seg-tab" :class="tab === '{{ $key }}' ? 'active' : ''">
                    {{ $tab['label'] }} ({{ $tab['count'] }})
                </button>
            @endforeach
        </div>

        <div x-show="tab === 'indoor'" class="operator-group-toolbar">
            <span class="operator-group-toolbar-label">Tampilkan berdasarkan:</span>
            @foreach (['order' => 'Per Order', 'division' => 'By Divisi', 'product' => 'By Produk'] as $mode => $label)
                <a href="{{ route($routeName, array_merge($query, ['tab' => 'indoor', 'group_by' => $mode])) }}"
                   class="seg-tab {{ $groupBy === $mode ? 'active' : '' }}"
                   aria-current="{{ $groupBy === $mode ? 'page' : 'false' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($slot->isNotEmpty())
        <div class="operator-queue-actions">{{ $slot }}</div>
    @endif
</div>
