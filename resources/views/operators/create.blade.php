<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Tambah Operator</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-xl">
        <form method="POST" action="{{ route('operators.store') }}" class="space-y-4">
            @csrf
            @include('operators._form')

            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan
                </button>
                <a href="{{ route('operators.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
