@php $kategoriProdukIndoor = $kategoriProdukIndoor ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdDivs" value="Kode Divisi" />
        <x-text-input id="KdDivs" name="KdDivs" type="text" class="mt-1 block w-full"
            value="{{ old('KdDivs', $kategoriProdukIndoor?->KdDivs) }}" maxlength="2" required autofocus />
        <x-input-error :messages="$errors->get('KdDivs')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $kategoriProdukIndoor?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="NmDivs" value="Nama Divisi" />
    <x-text-input id="NmDivs" name="NmDivs" type="text" class="mt-1 block w-full"
        value="{{ old('NmDivs', $kategoriProdukIndoor?->NmDivs) }}" maxlength="19" placeholder="Print Dokumen" required />
    <x-input-error :messages="$errors->get('NmDivs')" class="mt-1" />
</div>
