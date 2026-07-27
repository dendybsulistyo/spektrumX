<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Customer Aktif</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode customer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('customers.aktif') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 w-12">No</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Customer</th>
                    <th class="px-4 py-3">Kota</th>
                    <th class="px-4 py-3">Telepon</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Tanggal Transaksi Terakhir</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $customers->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $customer->KdCust }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $customer->NmCust }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->Kota }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $customer->Telp }}</td>
                        <td class="px-4 py-3">
                            @if ($customer->is_vip)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    VIP
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Reguler
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($customer->tanggal_transaksi)
                                <span class="text-gray-700">{{ \Carbon\Carbon::parse($customer->tanggal_transaksi)->format('d-m-Y') }}</span>
                            @else
                                <span class="text-gray-400 italic">Belum pernah order</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
