@php $user = $user ?? null; @endphp

<div>
    <x-input-label for="name" value="Nama" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
        value="{{ old('name', $user?->name) }}" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-1" />
</div>

<div>
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
        value="{{ old('email', $user?->email) }}" required />
    <x-input-error :messages="$errors->get('email')" class="mt-1" />
</div>

<div>
    <x-input-label for="password" :value="$user ? 'Password (kosongkan jika tidak diubah)' : 'Password'" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
    <x-input-error :messages="$errors->get('password')" class="mt-1" />
</div>

<div>
    <x-input-label for="role_id" value="Role" />
    <select id="role_id" name="role_id"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Belum ada role --</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected(old('role_id', $user?->role_id) == $role->id)>{{ $role->label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role_id')" class="mt-1" />
</div>
