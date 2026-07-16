@php $hargaArtwork = $hargaArtwork ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdProd" value="Kode Produk" />
        <x-text-input id="KdProd" name="KdProd" type="text" class="mt-1 block w-full"
            value="{{ old('KdProd', $hargaArtwork?->KdProd) }}" maxlength="4" required autofocus />
        <x-input-error :messages="$errors->get('KdProd')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $hargaArtwork?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="KdDivs" value="Kategori" />
    <select id="KdDivs" name="KdDivs"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Belum ada kategori --</option>
        @foreach ($kategoriList as $k)
            <option value="{{ $k->KdDivs }}" @selected(old('KdDivs', $hargaArtwork?->KdDivs) === $k->KdDivs)>{{ $k->NmDivs }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('KdDivs')" class="mt-1" />
</div>

<div>
    <x-input-label for="NmProd" value="Nama Produk" />
    <x-text-input id="NmProd" name="NmProd" type="text" class="mt-1 block w-full"
        value="{{ old('NmProd', $hargaArtwork?->NmProd) }}" maxlength="30" required />
    <x-input-error :messages="$errors->get('NmProd')" class="mt-1" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-input-label for="HargaStd" value="Harga Standar (Rp)" />
        <x-text-input id="HargaStd" name="HargaStd" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaStd', $hargaArtwork?->HargaStd) }}" required />
        <x-input-error :messages="$errors->get('HargaStd')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="HargaMin" value="Harga Minimum (Rp)" />
        <x-text-input id="HargaMin" name="HargaMin" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaMin', $hargaArtwork?->HargaMin) }}" required />
        <x-input-error :messages="$errors->get('HargaMin')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="Satuan" value="Satuan" />
        <x-text-input id="Satuan" name="Satuan" type="text" class="mt-1 block w-full"
            value="{{ old('Satuan', $hargaArtwork?->Satuan) }}" maxlength="8" placeholder="m2, pcs, dll" required />
        <x-input-error :messages="$errors->get('Satuan')" class="mt-1" />
    </div>
</div>

<div class="border-t pt-4 space-y-2">
    <label class="flex items-center gap-2">
        <input type="checkbox" name="isPjLb" value="1" {{ old('isPjLb', $hargaArtwork?->isPjLb) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm text-gray-700">Harga dihitung dari Panjang × Lebar (produk cetak per meter)</span>
    </label>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="isHPilih" value="1" {{ old('isHPilih', $hargaArtwork?->isHPilih) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm text-gray-700">Pakai harga bertingkat sesuai jumlah qty</span>
    </label>
</div>
