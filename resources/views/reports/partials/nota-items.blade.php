{{-- Rincian item satu nota pada rekap per nota; dibuka dengan klik "N item". --}}
<details class="nota-items">
    <summary>{{ $items->count() }} item</summary>
    <ol>
        @foreach ($items as $item)
            <li>
                <strong>{{ $item->description ?: '-' }}</strong>
                <span>{{ $item->product }}</span>
                <span class="nota-item-calc">
                    @if ((float) $item->length > 0 || (float) $item->width > 0)
                        {{ number_format((float) $item->length, 2, ',', '.') }} × {{ number_format((float) $item->width, 2, ',', '.') }} ·
                    @endif
                    {{ number_format((float) $item->qty, 0, ',', '.') }} × {{ $item->price !== null ? number_format($item->price, 0, ',', '.') : '-' }}
                    = {{ number_format($item->subtotal, 0, ',', '.') }}
                </span>
            </li>
        @endforeach
    </ol>
</details>
