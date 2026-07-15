@php $produk = $produk ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdProd" value="Kode Produk" />
        <x-text-input id="KdProd" name="KdProd" type="text" class="mt-1 block w-full font-mono"
            value="{{ old('KdProd', $produk?->KdProd) }}" maxlength="4" required autofocus />
        <x-input-error :messages="$errors->get('KdProd')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $produk?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="NmProd" value="Nama Produk" />
    <x-text-input id="NmProd" name="NmProd" type="text" class="mt-1 block w-full"
        value="{{ old('NmProd', $produk?->NmProd) }}" maxlength="30" required />
    <x-input-error :messages="$errors->get('NmProd')" class="mt-1" />
</div>

<div class="grid grid-cols-3 gap-4">
    <div>
        <x-input-label for="HargaStd" value="Harga Standar (Rp)" />
        <x-text-input id="HargaStd" name="HargaStd" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaStd', $produk?->HargaStd) }}" required />
        <x-input-error :messages="$errors->get('HargaStd')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="HargaMin" value="Harga Minimum (Rp)" />
        <x-text-input id="HargaMin" name="HargaMin" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaMin', $produk?->HargaMin) }}" required />
        <x-input-error :messages="$errors->get('HargaMin')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="Satuan" value="Satuan" />
        <x-text-input id="Satuan" name="Satuan" type="text" class="mt-1 block w-full"
            value="{{ old('Satuan', $produk?->Satuan) }}" maxlength="8" placeholder="m2, pcs, dll" required />
        <x-input-error :messages="$errors->get('Satuan')" class="mt-1" />
    </div>
</div>

<div class="border-t pt-4 space-y-2">
    <label class="flex items-center gap-2">
        <input type="checkbox" name="isPjLb" value="1" {{ old('isPjLb', $produk?->isPjLb) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm text-gray-700">Harga dihitung dari Panjang × Lebar (produk cetak per meter)</span>
    </label>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="isHPilih" value="1" {{ old('isHPilih', $produk?->isHPilih) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm text-gray-700">Pakai harga bertingkat sesuai jumlah qty</span>
    </label>
</div>
