<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Buat Order Artwork</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('order-artwork.store') }}">
            @csrf
            @include('order-artwork._form')
        </form>
    </div>
</x-app-layout>
