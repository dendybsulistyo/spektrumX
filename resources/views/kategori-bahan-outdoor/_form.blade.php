@php $kategoriBahanOutdoor = $kategoriBahanOutdoor ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="KdGrup" value="Kode Grup" />
        <x-text-input id="KdGrup" name="KdGrup" type="text" class="mt-1 block w-full"
            value="{{ old('KdGrup', $kategoriBahanOutdoor?->KdGrup) }}" maxlength="3" required autofocus />
        <x-input-error :messages="$errors->get('KdGrup')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="NoUrut" value="Nomor Urut" />
        <x-text-input id="NoUrut" name="NoUrut" type="number" class="mt-1 block w-full"
            value="{{ old('NoUrut', $kategoriBahanOutdoor?->NoUrut) }}" required />
        <x-input-error :messages="$errors->get('NoUrut')" class="mt-1" />
    </div>
</div>

<div>
    <x-input-label for="NmGrup" value="Nama Grup Bahan" />
    <x-text-input id="NmGrup" name="NmGrup" type="text" class="mt-1 block w-full"
        value="{{ old('NmGrup', $kategoriBahanOutdoor?->NmGrup) }}" maxlength="50" placeholder="STIKER GLOSY RITRAMA" required />
    <x-input-error :messages="$errors->get('NmGrup')" class="mt-1" />
</div>
