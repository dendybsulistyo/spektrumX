<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Edit User</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-xl">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('users._form')

            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan Perubahan
                </button>
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
