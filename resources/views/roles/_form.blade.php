@php $role = $role ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Kode Role (unik, tanpa spasi)" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full font-mono"
            value="{{ old('name', $role?->name) }}" maxlength="50" placeholder="kasir" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="label" value="Nama Role" />
        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full"
            value="{{ old('label', $role?->label) }}" maxlength="50" placeholder="Kasir" required />
        <x-input-error :messages="$errors->get('label')" class="mt-1" />
    </div>
</div>

<div class="border-t pt-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Akses / Permission</h3>

    @php $selected = old('permissions', $role?->permissions ?? []); @endphp

    <div class="space-y-4">
        @foreach ($permissionGroups as $groupName => $permissions)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">{{ $groupName }}</p>
                <div class="space-y-1.5 pl-2">
                    @foreach ($permissions as $key => $description)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                   {{ in_array($key, $selected) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">{{ $description }}</span>
                            <span class="text-xs text-gray-400 font-mono">({{ $key }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
</div>
