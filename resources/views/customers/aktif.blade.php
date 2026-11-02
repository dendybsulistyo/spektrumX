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
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Cari</button>
                @if (request('search'))
                    <a href="{{ route('customers.aktif') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        @php $canDelete = auth()->user()->hasPermission('customers.delete'); @endphp
        <div x-data="{ selected: [] }">
        @if ($canDelete)
            <form id="bulk-delete-customers" method="POST" action="{{ route('customers.bulk-destroy') }}"
                  @submit="if (! confirm(`Hapus ${selected.length} customer terpilih? Customer yang sudah punya transaksi akan dilewati.`)) $event.preventDefault()"
                  class="px-4 py-2 border-b flex items-center gap-3 text-sm">
                @csrf
                <template x-for="kd in selected" :key="kd"><input type="hidden" name="kd_cust[]" :value="kd"></template>
                <button type="submit" :disabled="selected.length === 0"
                        class="px-3 py-1.5 rounded-md bg-red-700 text-white font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-text="'Hapus Terpilih (' + selected.length + ')'"></span>
                </button>
                <span class="text-xs text-gray-500">Centang hanya tersedia untuk customer yang belum pernah order.</span>
            </form>
        @endif
        <div class="overflow-x-auto">
        <table class="w-full text-[13px] min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    @if ($canDelete)<th class="px-3 py-2 w-8"></th>@endif
                    <th class="px-3 py-2 w-12">No</th>
                    <th class="px-3 py-2">Kode</th>
                    <th class="px-3 py-2">Nama Customer</th>
                    <th class="px-3 py-2">Kota</th>
                    <th class="px-3 py-2">Telepon</th>
                    <th class="px-3 py-2">Tipe</th>
                    <th class="px-3 py-2">Tanggal Transaksi Terakhir</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($customers as $customer)
                    <tr>
                        @if ($canDelete)
                            <td class="px-3 py-2">
                                @unless ($customer->tanggal_transaksi)
                                    <input type="checkbox" value="{{ $customer->KdCust }}" x-model="selected"
                                           class="rounded border-gray-300" aria-label="Pilih {{ $customer->NmCust }}">
                                @endunless
                            </td>
                        @endif
                        <td class="px-3 py-2 text-gray-400">{{ $customers->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">{{ $customer->KdCust }}</td>
                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $customer->NmCust }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $customer->Kota }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $customer->Telp }}</td>
                        <td class="px-3 py-2">
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
                        <td class="px-3 py-2">
                            @if ($customer->tanggal_transaksi)
                                <span class="text-gray-700">{{ \Carbon\Carbon::parse($customer->tanggal_transaksi)->format('d-m-Y') }}</span>
                            @else
                                <span class="text-gray-400 italic">Belum pernah order</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canDelete ? 8 : 7 }}" class="px-4 py-6 text-center text-gray-400">Belum ada data customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        </div>

        <div class="p-4">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
