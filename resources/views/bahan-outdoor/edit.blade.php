<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit Bahan Outdoor</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('bahan-outdoor.update', $bahanOutdoor) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('bahan-outdoor._form')

            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan Perubahan
                </button>
                <a href="{{ route('bahan-outdoor.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
