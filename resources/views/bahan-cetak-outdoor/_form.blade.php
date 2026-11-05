@php $bahanCetakOutdoor = $bahanCetakOutdoor ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $bahanCetakOutdoor?->NoUrut) }}" :required="! $bahanCetakOutdoor" :readonly="(bool) $bahanCetakOutdoor" />
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

<label class="flex items-start gap-2">
    <input type="hidden" name="lewati_layout_cetak" value="0">
    <input type="checkbox" name="lewati_layout_cetak" value="1" {{ old('lewati_layout_cetak', $bahanCetakOutdoor?->lewati_layout_cetak) ? 'checked' : '' }}
           class="mt-0.5 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
    <span class="text-sm text-gray-700">Lewati Layout &amp; Cetak
        <span class="block text-xs text-gray-500">Untuk laminasi dan sejenisnya: item outdoor dengan bahan ini langsung masuk Finishing saat SO dibuat, lalu lanjut Back Office, Bungkus, Pengambilan seperti biasa. Berlaku untuk order baru.</span>
    </span>
</label>
