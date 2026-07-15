@php
    $customer = $customer ?? null;
    $isVip = old('is_vip', $customer?->is_vip) ? true : false;
@endphp

<div>
    <x-input-label for="KdCust" value="Kode Customer" />
    <x-text-input id="KdCust" name="KdCust" type="text" class="mt-1 block w-full font-mono"
        value="{{ old('KdCust', $customer?->KdCust) }}" maxlength="6" required autofocus />
    <x-input-error :messages="$errors->get('KdCust')" class="mt-1" />
</div>

<div>
    <x-input-label for="NmCust" value="Nama Customer" />
    <x-text-input id="NmCust" name="NmCust" type="text" class="mt-1 block w-full"
        value="{{ old('NmCust', $customer?->NmCust) }}" maxlength="50" required />
    <x-input-error :messages="$errors->get('NmCust')" class="mt-1" />
</div>

<div>
    <x-input-label for="Alamat" value="Alamat" />
    <x-text-input id="Alamat" name="Alamat" type="text" class="mt-1 block w-full"
        value="{{ old('Alamat', $customer?->Alamat) }}" maxlength="60" required />
    <x-input-error :messages="$errors->get('Alamat')" class="mt-1" />
</div>

<div>
    <x-input-label for="Kota" value="Kota" />
    <x-text-input id="Kota" name="Kota" type="text" class="mt-1 block w-full"
        value="{{ old('Kota', $customer?->Kota) }}" maxlength="25" required />
    <x-input-error :messages="$errors->get('Kota')" class="mt-1" />
</div>

<div>
    <x-input-label for="Telp" value="Telepon" />
    <x-text-input id="Telp" name="Telp" type="text" class="mt-1 block w-full"
        value="{{ old('Telp', $customer?->Telp) }}" maxlength="40" required />
    <x-input-error :messages="$errors->get('Telp')" class="mt-1" />
</div>

<div class="border-t pt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_vip" value="1" id="is_vip"
               {{ $isVip ? 'checked' : '' }}
               onchange="document.getElementById('batas-wrapper').classList.toggle('hidden', !this.checked)"
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm font-medium text-gray-700">Customer VIP (punya limit piutang)</span>
    </label>

    <div id="batas-wrapper" class="mt-3 {{ $isVip ? '' : 'hidden' }}">
        <x-input-label for="Batas" value="Limit Piutang (Rp)" />
        <x-text-input id="Batas" name="Batas" type="number" step="0.01" class="mt-1 block w-full max-w-xs"
            value="{{ old('Batas', $customer?->limit?->Batas) }}" />
        <x-input-error :messages="$errors->get('Batas')" class="mt-1" />
        @if ($customer?->limit)
            <p class="text-xs text-gray-500 mt-1">Piutang berjalan saat ini: Rp {{ number_format($customer->limit->Total, 0, ',', '.') }}</p>
        @endif
    </div>
</div>
