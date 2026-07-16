@php $printer = $printer ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdPrn" value="Kode Printer" />
        <x-text-input id="KdPrn" name="KdPrn" type="text" class="mt-1 block w-full"
            value="{{ old('KdPrn', $printer?->KdPrn) }}" maxlength="2" required autofocus />
        <x-input-error :messages="$errors->get('KdPrn')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $printer?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="NmPrn" value="Nama Printer" />
    <x-text-input id="NmPrn" name="NmPrn" type="text" class="mt-1 block w-full"
        value="{{ old('NmPrn', $printer?->NmPrn) }}" maxlength="20" placeholder="Mimaki UV (4c)" required />
    <x-input-error :messages="$errors->get('NmPrn')" class="mt-1" />
</div>
