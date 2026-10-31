<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Approval Cetak Ulang</h2>
    </x-slot>

    <div class="space-y-5">
        <section class="overflow-hidden rounded border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-2.5">
                <h3 class="text-sm font-semibold text-gray-900">Menunggu persetujuan</h3>
                <p class="text-xs text-gray-500">Cetak ulang bebas {{ \App\Services\ReprintGate::FREE_MINUTES }} menit setelah cetak pertama. Lewat dari itu petugas mengajukan di sini; satu persetujuan berlaku untuk satu kali cetak.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-[13px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-3 py-2">Diminta</th><th class="px-3 py-2">Dokumen</th><th class="px-3 py-2">Petugas</th><th class="px-3 py-2">Alasan</th><th class="px-3 py-2 text-right">Keputusan</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($pending as $row)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600">{{ $row->created_at->format('d/m H:i') }}</td>
                                <td class="px-3 py-2"><span class="font-semibold text-gray-900">{{ $row->document_number }}</span> <span class="text-xs text-gray-500">{{ \App\Services\ReprintGate::KIND_LABELS[$row->kind] ?? $row->kind }}</span></td>
                                <td class="px-3 py-2">{{ $row->requestedBy?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $row->reason }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <form method="POST" action="{{ route('reprint-approval.decide', $row) }}" class="inline">
                                        @csrf
                                        <button name="keputusan" value="setujui" class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Setujui</button>
                                        <button name="keputusan" value="tolak" class="ml-1 rounded border border-red-300 px-3 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-gray-400">Tidak ada permintaan cetak ulang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-2.5">
                <h3 class="text-sm font-semibold text-gray-900">Riwayat cetak ulang</h3>
                <p class="text-xs text-gray-500">200 cetak ulang terakhir.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-[13px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-3 py-2">Waktu</th><th class="px-3 py-2">Dokumen</th><th class="px-3 py-2 text-right">Cetak ke</th><th class="px-3 py-2">Petugas</th><th class="px-3 py-2">Alasan</th><th class="px-3 py-2">Izin</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 text-gray-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2"><span class="font-semibold text-gray-900">{{ $log->document_number }}</span> <span class="text-xs text-gray-500">{{ \App\Services\ReprintGate::KIND_LABELS[$log->kind] ?? $log->kind }}</span></td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $log->print_no }}</td>
                                <td class="px-3 py-2">{{ $log->user?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $log->reason }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">{{ \App\Models\ReprintLog::MODE_LABELS[$log->mode] ?? $log->mode }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">Belum ada cetak ulang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
