<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ isset($replacementOrder) ? 'Buat Nota Pengganti Indoor' : 'Buat Order Indoor' }}</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ isset($replacementOrder) ? route('kasir.replacement.store.indoor') : route('order-indoor.store') }}"
              x-data="{ destinationOpen: {{ $errors->any() && old('payment_queue') ? 'true' : 'false' }} }">
            @csrf
            @if (isset($sourceJobSheet) && $sourceJobSheet)
                <div class="mb-4 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                    Diambil dari Lembar Kerja CS #{{ $sourceJobSheet->id }} — {{ $sourceJobSheet->customer_name }}.
                    Folder: <strong>{{ $sourceJobSheet->indoorFolderLabel() ?? 'Belum ditentukan' }}</strong>. Pilih produk sebelum menyimpan order.
                </div>
            @endif
            @if (isset($replacementOrder))
                <input type="hidden" name="replacement_order_id" value="{{ $replacementOrder->id }}">
                <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Nota pengganti untuk <strong>{{ $replacementOrder->NoOrder }}</strong>. Customer dan item lama disalin agar dapat diedit. Kredit pembayaran lama sebesar Rp {{ number_format($replacementOrder->jumlah_dibayar, 0, ',', '.') }} akan diperhitungkan saat pembayaran nota baru.
                </div>
            @endif
            @include('order-indoor._form')
            @unless(isset($replacementOrder))
                <x-order-payment-destination-modal />
            @endunless
        </form>
    </div>
</x-app-layout>
