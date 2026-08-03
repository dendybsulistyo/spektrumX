<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Monitoring File Masuk</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari no order, nama customer, atau nama file..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('file.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-[13px] min-w-[720px]">
            <thead class="bg-gray-900 text-left text-xs uppercase text-white">
                <tr>
                    <th class="px-3 py-2">No Order</th>
                    <th class="px-3 py-2">Jenis</th>
                    <th class="px-3 py-2">Nama File</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2">Tanggal</th>
                    <th class="px-3 py-2">User Input</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($files as $file)
                    <tr>
                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $file->no_order }}</td>
                        <td class="px-3 py-2">
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs',
                                'bg-blue-50 text-blue-700' => $file->jenis === 'Indoor',
                                'bg-amber-50 text-amber-700' => $file->jenis === 'Outdoor',
                                'bg-purple-50 text-purple-700' => $file->jenis === 'Artwork',
                            ])>{{ $file->jenis }}</span>
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $file->nama_file ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $file->customer ? ucwords(mb_strtolower($file->customer)) : '-' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $file->tanggal }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $file->user_input ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada order masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $files->links() }}
        </div>
    </div>
</x-app-layout>
