<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Tambah Harga Indoor</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl"
         x-data="{ mode: '{{ old('kategori_mode', $kategoriList->isEmpty() ? 'new' : 'existing') }}' }">
        <form method="POST" action="{{ route('kategori.store') }}" class="space-y-4">
            @csrf

            <div class="border-b pb-4">
                <x-input-label value="Divisi / Kategori" />
                <p class="text-xs text-gray-400 mb-2">Pilih kategori yang sudah ada, atau buat kategori baru sekaligus.</p>

                <div class="flex gap-4 mb-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="kategori_mode" value="existing" x-model="mode"
                               {{ $kategoriList->isEmpty() ? 'disabled' : '' }}
                               class="border-gray-300 text-gray-900 focus:ring-gray-900">
                        Pakai kategori yang sudah ada
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="kategori_mode" value="new" x-model="mode"
                               class="border-gray-300 text-gray-900 focus:ring-gray-900">
                        Buat kategori baru
                    </label>
                </div>

                <div x-show="mode === 'existing'" x-cloak>
                    <select name="KdDivs" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($kategoriList as $k)
                            <option value="{{ $k->KdDivs }}" @selected(old('KdDivs') === $k->KdDivs)>{{ $k->NmDivs }} ({{ $k->KdDivs }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('KdDivs')" class="mt-1" />
                </div>

                <div x-show="mode === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="new_KdDivs" value="Kode Kategori" />
                        <x-text-input id="new_KdDivs" name="new_KdDivs" type="text" class="mt-1 block w-full"
                            value="{{ old('new_KdDivs') }}" maxlength="2" />
                        <x-input-error :messages="$errors->get('new_KdDivs')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="new_NmDivs" value="Nama Kategori" />
                        <x-text-input id="new_NmDivs" name="new_NmDivs" type="text" class="mt-1 block w-full"
                            value="{{ old('new_NmDivs') }}" maxlength="19" placeholder="Dye Sublimation" />
                        <x-input-error :messages="$errors->get('new_NmDivs')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="KategoriNoUrut" value="Nomor Urut Kategori" />
                        <x-text-input id="KategoriNoUrut" name="KategoriNoUrut" type="number" class="mt-1 block w-full"
                            value="{{ old('KategoriNoUrut') }}" />
                        <x-input-error :messages="$errors->get('KategoriNoUrut')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label value="Produk Pertama di Kategori Ini" />
                <p class="text-xs text-gray-400 mb-2">Setiap kategori butuh minimal satu produk supaya harganya bisa ditampilkan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="KdProd" value="Kode Produk" />
                    <x-text-input id="KdProd" name="KdProd" type="text" class="mt-1 block w-full"
                        value="{{ old('KdProd') }}" maxlength="4" required />
                    <x-input-error :messages="$errors->get('KdProd')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="NoUrut" value="Nomor Urut Produk" />
                    <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
                        value="{{ old('NoUrut') }}" required />
                    <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="NmProd" value="Nama Produk" />
                <x-text-input id="NmProd" name="NmProd" type="text" class="mt-1 block w-full"
                    value="{{ old('NmProd') }}" maxlength="30" required />
                <x-input-error :messages="$errors->get('NmProd')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="HargaStd" value="Harga Standar (Rp)" />
                    <x-text-input id="HargaStd" name="HargaStd" type="number" step="0.01" class="mt-1 block w-full"
                        value="{{ old('HargaStd') }}" required />
                    <x-input-error :messages="$errors->get('HargaStd')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="HargaMin" value="Harga Minimum (Rp)" />
                    <x-text-input id="HargaMin" name="HargaMin" type="number" step="0.01" class="mt-1 block w-full"
                        value="{{ old('HargaMin') }}" required />
                    <x-input-error :messages="$errors->get('HargaMin')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="Satuan" value="Satuan" />
                    <x-text-input id="Satuan" name="Satuan" type="text" class="mt-1 block w-full"
                        value="{{ old('Satuan') }}" maxlength="8" placeholder="m2, pcs, dll" required />
                    <x-input-error :messages="$errors->get('Satuan')" class="mt-1" />
                </div>
            </div>

            <div class="border-t pt-4 space-y-2">
                <div>
                    <x-input-label for="isPjLb" value="Cara Hitung Harga Order" />
                    <select id="isPjLb" name="isPjLb" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach (\App\Models\Produk::PJLB_LABELS as $code => $label)
                            <option value="{{ $code }}" @selected((int) old('isPjLb') === $code)>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('isPjLb')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="isHPilih" value="1" {{ old('isHPilih') == 1 ? 'checked' : '' }}
                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                    <span class="text-sm text-gray-700">Pakai harga bertingkat sesuai jumlah qty</span>
                </label>
            </div>

            <div class="pt-2 flex gap-3 border-t">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan
                </button>
                <a href="{{ route('kategori.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
