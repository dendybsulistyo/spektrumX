@php $operator = $operator ?? null; @endphp

<div>
    <x-input-label for="KdOpr" value="Kode Operator" />
    <x-text-input id="KdOpr" name="KdOpr" type="text" class="mt-1 block w-full"
        value="{{ old('KdOpr', $operator?->KdOpr) }}" maxlength="4" required autofocus />
    <x-input-error :messages="$errors->get('KdOpr')" class="mt-1" />
</div>

<div>
    <x-input-label for="NmOpr" value="Nama Operator" />
    <x-text-input id="NmOpr" name="NmOpr" type="text" class="mt-1 block w-full"
        value="{{ old('NmOpr', $operator?->NmOpr) }}" maxlength="50" required />
    <x-input-error :messages="$errors->get('NmOpr')" class="mt-1" />
</div>

<div class="border-t pt-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="Status" value="1"
               {{ old('Status', $operator?->Status ?? true) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <span class="text-sm font-medium text-gray-700">Operator aktif</span>
    </label>
</div>
