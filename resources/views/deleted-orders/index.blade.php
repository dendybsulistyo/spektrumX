<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Riwayat Order Dihapus</h2></x-slot>

    <style>
        #deleted-orders table { width:100%; border-collapse:collapse; font-size:13px; }
        #deleted-orders th { padding:8px 10px; background:#f1f5f9; border-bottom:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        #deleted-orders td { padding:8px 10px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
        #deleted-orders .detail td { background:#f8fafc; }
        #deleted-orders .detail table { font-size:12px; }
        #deleted-orders .detail th { background:transparent; padding:4px 8px; }
        #deleted-orders .detail td { padding:4px 8px; border-bottom-color:#eef2f6; }
    </style>

    <div id="deleted-orders" class="mx-auto max-w-[1400px] space-y-4">
        <form method="GET" class="flex flex-wrap items-end gap-2 rounded border border-slate-200 bg-white p-4">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">Cari No. Order / customer / alasan
                <input name="search" value="{{ $search }}" class="h-[34px] rounded border border-slate-300 px-2 text-sm" style="width:280px;">
            </label>
            <button class="h-[34px] rounded bg-slate-800 px-4 text-sm font-semibold text-white">Tampilkan</button>
        </form>

        <div class="overflow-x-auto rounded border border-slate-200 bg-white" x-data="{ open: null }">
            <table>
                <thead><tr>
                    <th>Dihapus</th><th>No. Order</th><th>Customer</th><th>Tgl Order</th><th>Status saat dihapus</th>
                    <th style="text-align:right;">Total</th><th>Alasan</th><th>Oleh</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $snapshot = $log->snapshot ?? []; @endphp
                        <tr>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->deleted_at?->format('d-m-Y H:i') }}</td>
                            <td class="whitespace-nowrap"><span class="font-semibold text-gray-900">{{ $log->no_order }}</span> <span class="text-xs text-gray-400">{{ ucfirst($log->order_type) }}</span></td>
                            <td class="text-gray-700">{{ $log->customer_name ?: $log->kd_cust ?: '-' }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->tgl_order?->format('d-m-Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->status ?? '-' }} · {{ $log->status_bayar ?? '-' }}
                                @if ($log->kredit_dikembalikan > 0)<div class="text-xs text-amber-700">Plafon dikembalikan Rp {{ number_format($log->kredit_dikembalikan, 0, ',', '.') }}</div>@endif
                            </td>
                            <td class="whitespace-nowrap text-right">Rp {{ number_format($log->total, 0, ',', '.') }}</td>
                            <td class="text-gray-700" style="min-width:200px;">{{ $log->alasan }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->dihapus_oleh ?? '-' }}</td>
                            <td class="text-right"><button type="button" class="text-xs font-semibold text-indigo-700" @click="open = open === {{ $log->id }} ? null : {{ $log->id }}" x-text="open === {{ $log->id }} ? 'Tutup' : 'Detail'"></button></td>
                        </tr>
                        <tr class="detail" x-show="open === {{ $log->id }}" x-cloak>
                            <td colspan="9">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Item ({{ count($snapshot['items'] ?? []) }})</p>
                                        <table><tbody>
                                            @forelse ($snapshot['items'] ?? [] as $item)
                                                <tr><td>{{ $item['Judul'] ?? $item['NmFile'] ?? $item['NmProd'] ?? '-' }}</td><td class="whitespace-nowrap">{{ $item['Panjang'] ?? '-' }} × {{ $item['Lebar'] ?? '-' }}</td><td>Qty {{ $item['Qty'] ?? '-' }}</td></tr>
                                            @empty
                                                <tr><td class="text-gray-400">-</td></tr>
                                            @endforelse
                                        </tbody></table>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Pembayaran ({{ count($snapshot['payments'] ?? []) }})</p>
                                        <table><tbody>
                                            @forelse ($snapshot['payments'] ?? [] as $payment)
                                                <tr><td>{{ $payment['jenis'] ?? '-' }}</td><td>{{ $payment['cara_bayar'] ?? '-' }}</td><td class="text-right">Rp {{ number_format((float) ($payment['jumlah'] ?? 0), 0, ',', '.') }}</td><td class="text-gray-500">{{ $payment['created_at'] ?? '' }}</td></tr>
                                            @empty
                                                <tr><td class="text-gray-400">-</td></tr>
                                            @endforelse
                                        </tbody></table>
                                        <p class="mb-1 mt-3 text-xs font-semibold uppercase text-slate-500">Dokumen</p>
                                        <p>{{ collect($snapshot['documents'] ?? [])->pluck('number')->filter()->implode(', ') ?: '-' }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Jurnal yang ikut dihapus ({{ count($snapshot['journals'] ?? []) }} baris)</p>
                                        <table>
                                            <thead><tr><th>Tanggal</th><th>No. Trans</th><th>Akun</th><th>Keterangan</th><th style="text-align:right;">Debet</th><th style="text-align:right;">Kredit</th></tr></thead>
                                            <tbody>
                                                @forelse ($snapshot['journals'] ?? [] as $line)
                                                    <tr><td>{{ $line['TgTrans'] ?? '-' }}</td><td>{{ $line['NoTrans'] ?? '-' }}</td><td>{{ $line['NoAkun'] ?? '-' }}</td><td>{{ $line['KetMT'] ?? '-' }}</td>
                                                        <td class="text-right">{{ number_format((float) ($line['Debet'] ?? 0), 0, ',', '.') }}</td><td class="text-right">{{ number_format((float) ($line['Kredit'] ?? 0), 0, ',', '.') }}</td></tr>
                                                @empty
                                                    <tr><td colspan="6" class="text-gray-400">Tidak ada jurnal.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-8 text-center text-gray-400">Belum ada order yang dihapus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ number_format($logs->total(), 0, ',', '.') }} order dihapus</span>
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
