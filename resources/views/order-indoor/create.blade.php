<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Buat Order Indoor</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('order-indoor.store') }}">
            @csrf
            @include('order-indoor._form')
        </form>
    </div>
</x-app-layout>
