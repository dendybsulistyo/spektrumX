<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit Order {{ $order->NoOrder }}</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('order-indoor.update', $order) }}">
            @csrf
            @method('PUT')
            @include('order-indoor._form')

            <div class="pt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan Perubahan
                </button>
                <a href="{{ route('order-indoor.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
