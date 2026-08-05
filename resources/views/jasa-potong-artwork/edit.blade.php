<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Pengaturan Jasa Potong Artwork</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-xl">
        <div class="mb-6 p-4 bg-gray-50 rounded-md border border-gray-200">
            <p class="text-sm text-gray-700 font-medium mb-1">Rumus perhitungan Ongkos Jasa Potong Artwork</p>
            <p class="text-sm text-gray-600 font-mono">Ongkos = ((Pisau Turun × Jumlah Kertas × Tebal Kertas) / 10) + X</p>
            <p class="text-xs text-gray-400 mt-2">
                Berlaku untuk produk Artwork dengan kode "Cara Hitung Harga Order" = 4.
                Pisau Turun, Jumlah Kertas, dan Tebal Kertas diisi per item saat membuat order artwork.
                X adalah biaya tetap di bawah ini, berlaku untuk semua produk artwork berkode tersebut —
                nilai ini terpisah dari Jasa Potong Indoor.
            </p>
        </div>

        <form method="POST" action="{{ route('jasa-potong-artwork.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="nilai_x" value="Nilai X (Rp)" />
                <x-text-input id="nilai_x" name="nilai_x" type="number" step="0.01" class="mt-1 block w-full"
                    value="{{ old('nilai_x', $konfigurasi->nilai_x) }}" required autofocus />
                <x-input-error :messages="$errors->get('nilai_x')" class="mt-1" />
            </div>

            <div class="pt-2 flex gap-3 border-t">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
