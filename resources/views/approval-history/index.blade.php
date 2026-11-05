<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">History Approval</h2>
    </x-slot>

    @php
        $statusStyles = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-700'];
    @endphp

    <div class="space-y-4">
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-white p-4">
            <label class="text-sm text-gray-700">Dari<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
            <label class="text-sm text-gray-700">Sampai<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
            <label class="text-sm text-gray-700">Jenis
                <select name="jenis" class="mt-1 block rounded-md border-gray-300 text-sm">
                    <option value="">Semua</option>
                    @foreach (\App\Http\Controllers\ApprovalHistoryController::KIND_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected($kind === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-gray-700">Status
                <select name="status" class="mt-1 block rounded-md border-gray-300 text-sm">
                    <option value="">Semua</option>
                    @foreach (\App\Http\Controllers\ApprovalHistoryController::STATUS_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-gray-700">Cari<input type="text" name="q" value="{{ $search }}" placeholder="No. order / nama" class="mt-1 block w-52 rounded-md border-gray-300 text-sm"></label>
            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Tampilkan</button>
        </form>

        <section class="overflow-hidden rounded border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-2.5">
                <h3 class="text-sm font-semibold text-gray-900">{{ $rows->count() }} pengajuan</h3>
                <p class="text-xs text-gray-500">Diskon &amp; Hutang VIP tercatat lengkap per pengajuan sejak 5 Nov 2026; sebelumnya hanya pengajuan terakhir per order.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-[13px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Diajukan</th><th class="px-3 py-2">Jenis</th><th class="px-3 py-2">No. Order</th>
                            <th class="px-3 py-2">Customer</th><th class="px-3 py-2">Diminta oleh</th><th class="px-3 py-2">Alasan / Nilai</th>
                            <th class="px-3 py-2">Keputusan</th><th class="px-3 py-2">Diputuskan oleh</th><th class="px-3 py-2">Waktu Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600">{{ $row->requested_at?->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ \App\Http\Controllers\ApprovalHistoryController::KIND_LABELS[$row->kind] }}</td>
                                <td class="whitespace-nowrap px-3 py-2 font-semibold text-gray-900">{{ $row->no_order }}</td>
                                <td class="px-3 py-2">{{ $row->customer }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $row->requested_name }}</td>
                                <td class="px-3 py-2">
                                    @if ($row->detail)<span class="font-semibold">{{ $row->detail }}</span>@endif
                                    @if ($row->detail && $row->reason) &middot; @endif
                                    {{ $row->reason }}
                                </td>
                                <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusStyles[$row->status] ?? '' }}">{{ \App\Http\Controllers\ApprovalHistoryController::STATUS_LABELS[$row->status] ?? $row->status }}</span></td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $row->decided_name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600">{{ $row->decided_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-3 py-8 text-center text-gray-400">Tidak ada pengajuan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
