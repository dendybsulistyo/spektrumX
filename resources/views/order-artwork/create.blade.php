<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ isset($replacementOrder) ? 'Buat Nota Pengganti Artwork' : 'Buat Order Artwork' }}</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ isset($replacementOrder) ? route('kasir.replacement.store.artwork') : route('order-artwork.store') }}">
            @csrf
            @if (isset($replacementOrder))
                <input type="hidden" name="replacement_order_id" value="{{ $replacementOrder->id }}">
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Nota pengganti untuk <strong>{{ $replacementOrder->NoOrder }}</strong>. Customer dan item lama disalin agar dapat diedit. Kredit pembayaran lama sebesar Rp {{ number_format($replacementOrder->jumlah_dibayar, 0, ',', '.') }} akan diperhitungkan saat pembayaran nota baru.
                </div>
            @endif
            @include('order-artwork._form')
        </form>
    </div>
</x-app-layout>
