<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit Order {{ $order->NoOrder }}</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('order-outdoor.update', $order) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('order-outdoor._form')
        </form>
    </div>
</x-app-layout>
