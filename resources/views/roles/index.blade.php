<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Role & Akses</h2>
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Role
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama Role</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Jumlah User</th>
                    <th class="px-4 py-3">Permission</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $role->label }}</td>
                        <td class="px-4 py-3 font-mono text-gray-500">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ count($role->permissions ?? []) }} permission</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('roles.edit', $role) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline"
                                  onsubmit="return confirm('Hapus role {{ $role->label }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada role.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
