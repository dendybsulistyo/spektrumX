@php $bahanCetakOutdoor = $bahanCetakOutdoor ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $bahanCetakOutdoor?->NoUrut) }}" required {{ $bahanCetakOutdoor ? 'readonly' : '' }} />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoCetak" value="Nomor Cetak" />
        <x-text-input id="NoCetak" name="NoCetak" type="text" class="mt-1 block w-full"
            value="{{ old('NoCetak', $bahanCetakOutdoor?->NoCetak) }}" maxlength="10" />
        <x-input-error :messages="$errors->get('NoCetak')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="NmBhn" value="Nama Bahan" />
    <x-text-input id="NmBhn" name="NmBhn" type="text" class="mt-1 block w-full"
        value="{{ old('NmBhn', $bahanCetakOutdoor?->NmBhn) }}" maxlength="30" placeholder="FT China 260-3.2" required autofocus />
    <x-input-error :messages="$errors->get('NmBhn')" class="mt-1" />
</div>
