<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">{{ $source ? 'Duplikat Role: '.$source->label : 'Tambah Role' }}</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        @if ($source)
            <p class="mb-4 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Permission disalin dari <strong>{{ $source->label }}</strong>. Ganti kode &amp; nama role, ubah permission bila perlu, lalu Simpan. Role asli tidak berubah.
            </p>
        @endif
        <form method="POST" action="{{ route('roles.store') }}" class="space-y-4">
            @csrf
            @include('roles._form')

            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan
                </button>
                <a href="{{ \App\Http\Middleware\KembaliKeHalamanIndex::url('roles.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
