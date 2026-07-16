@php $bahanOutdoor = $bahanOutdoor ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdBrgs" value="Kode Bahan" />
        <x-text-input id="KdBrgs" name="KdBrgs" type="text" class="mt-1 block w-full"
            value="{{ old('KdBrgs', $bahanOutdoor?->KdBrgs) }}" maxlength="8" required autofocus />
        <x-input-error :messages="$errors->get('KdBrgs')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $bahanOutdoor?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="KdGrup" value="Kategori" />
    <select id="KdGrup" name="KdGrup"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Belum ada kategori --</option>
        @foreach ($kategoriList as $k)
            <option value="{{ $k->KdGrup }}" @selected(old('KdGrup', $bahanOutdoor?->KdGrup) === $k->KdGrup)>{{ $k->NmGrup }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('KdGrup')" class="mt-1" />
</div>

<div>
    <x-input-label for="NmBrgs" value="Nama Bahan" />
    <x-text-input id="NmBrgs" name="NmBrgs" type="text" class="mt-1 block w-full"
        value="{{ old('NmBrgs', $bahanOutdoor?->NmBrgs) }}" maxlength="50" required />
    <x-input-error :messages="$errors->get('NmBrgs')" class="mt-1" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="Keters" value="Keterangan" />
        <x-text-input id="Keters" name="Keters" type="text" class="mt-1 block w-full"
            value="{{ old('Keters', $bahanOutdoor?->Keters) }}" maxlength="30" required />
        <x-input-error :messages="$errors->get('Keters')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="Satuan" value="Satuan" />
        <x-text-input id="Satuan" name="Satuan" type="text" class="mt-1 block w-full"
            value="{{ old('Satuan', $bahanOutdoor?->Satuan) }}" maxlength="10" placeholder="ROL, LBR, dll" required />
        <x-input-error :messages="$errors->get('Satuan')" class="mt-1" />
    </div>
</div>
