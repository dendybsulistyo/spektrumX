<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Data Customer</h2>
            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Customer
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode customer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('customers.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Customer</th>
                    <th class="px-4 py-3">Alamat</th>
                    <th class="px-4 py-3">Kota</th>
                    <th class="px-4 py-3">Telepon</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $customer->KdCust }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $customer->NmCust }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->Alamat }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->Kota }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->Telp }}</td>
                        <td class="px-4 py-3">
                            @if ($customer->is_vip)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    VIP · Rp {{ number_format($customer->limit->Batas, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Reguler
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('customers.edit', $customer) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline"
                                  onsubmit="return confirm('Hapus customer {{ $customer->NmCust }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
