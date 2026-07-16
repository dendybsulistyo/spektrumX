@php $hargaCetakOutdoor = $hargaCetakOutdoor ?? null; @endphp

<div>
    <x-input-label for="KdCtk" value="Kode Cetak" />
    <x-text-input id="KdCtk" name="KdCtk" type="text" class="mt-1 block w-full" :readonly="$hargaCetakOutdoor !== null"
        value="{{ old('KdCtk', $hargaCetakOutdoor?->KdCtk) }}" maxlength="4" required autofocus />
    <x-input-error :messages="$errors->get('KdCtk')" class="mt-1" />
    @if ($hargaCetakOutdoor)
        <p class="text-xs text-gray-500 mt-1">Kode cetak tidak bisa diubah karena jadi kunci utama data ini.</p>
    @endif
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="HargaStd" value="Harga Standar (Rp)" />
        <x-text-input id="HargaStd" name="HargaStd" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaStd', $hargaCetakOutdoor?->HargaStd) }}" required />
        <x-input-error :messages="$errors->get('HargaStd')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="HargaMin" value="Harga Minimum (Rp)" />
        <x-text-input id="HargaMin" name="HargaMin" type="number" step="0.01" class="mt-1 block w-full"
            value="{{ old('HargaMin', $hargaCetakOutdoor?->HargaMin) }}" required />
        <x-input-error :messages="$errors->get('HargaMin')" class="mt-1" />
    </div>
</div>
