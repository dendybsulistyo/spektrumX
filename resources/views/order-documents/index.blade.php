<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Dokumen SO / DO / Invoice</h2></x-slot>

    @push('styles')
        <style>
            #order-document-archive .doc-table { width:100%; border-collapse:collapse; font-size:13px; }
            #order-document-archive .doc-table th { padding:8px 10px; background:#f1f5f9; border-bottom:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
            #order-document-archive .doc-table td { padding:8px 10px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
            #order-document-archive .doc-table tr.is-void td { color:#94a3b8; }
            #order-document-archive .doc-link { display:inline-flex; align-items:center; gap:4px; margin:0 6px 4px 0; padding:3px 8px; border:1px solid #cbd5e1; border-radius:4px; color:#1e293b; font-family:var(--font-heading); font-size:12px; white-space:nowrap; text-decoration:none; }
            #order-document-archive .doc-link:hover { border-color:#334155; background:#f8fafc; }
            #order-document-archive .doc-link.so { border-color:#bfdbfe; color:#1d4ed8; }
            #order-document-archive .doc-link.do { border-color:#a7f3d0; color:#047857; }
            #order-document-archive .doc-link.inv { border-color:#cbd5e1; color:#334155; }
            #order-document-archive .print-count { color:#64748b; font-family:var(--font-body); font-size:11px; }
            #order-document-archive .muted { color:#94a3b8; }
            #order-document-archive .filters { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; }
            #order-document-archive .filters label { display:flex; flex-direction:column; gap:4px; color:#475569; font-size:12px; font-weight:600; }
            #order-document-archive .filters input,
            #order-document-archive .filters select { height:34px; padding:0 10px; border:1px solid #cbd5e1; border-radius:4px; background:#fff; color:#1e293b; font-size:13px; }
            #order-document-archive .filters input[name=search] { width:240px; }
            #order-document-archive .filters input[type=date] { width:140px; }
            #order-document-archive .filters select { width:150px; padding-right:28px; }
            #order-document-archive .filter-btn { display:inline-flex; align-items:center; height:34px; padding:0 14px; border:1px solid #1e293b; border-radius:4px; background:#1e293b; color:#fff; font-size:13px; font-weight:600; text-decoration:none; }
            #order-document-archive .filter-btn.secondary { border-color:#cbd5e1; background:#fff; color:#334155; }
            #order-document-archive .filter-note { margin:10px 0 0; color:#94a3b8; font-size:12px; }
        </style>
    @endpush

    <div id="order-document-archive" style="max-width:1240px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
        <div style="padding:14px 16px; background:#fff; border:1px solid #d7dee8; border-radius:4px;">
            <form method="GET" class="filters">
                <label>Cari
                    <input name="search" value="{{ $search }}" placeholder="No. SO / DO / Invoice / customer">
                </label>
                <label>Dari tanggal
                    <input type="date" name="dari" value="{{ request('dari') }}">
                </label>
                <label>Sampai tanggal
                    <input type="date" name="sampai" value="{{ request('sampai') }}">
                </label>
                {{-- DISEMBUNYIKAN SEMENTARA (23 Okt 2026, permintaan Yovita): filter status & dokumen. Controller tetap menerimanya.
                <label>Status bayar
                    <select name="status">
                        <option value="">Semua</option>
                        @foreach (['lunas' => 'Lunas', 'dp' => 'DP', 'hutang' => 'Hutang', 'batal' => 'Batal'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Dokumen
                    <select name="dokumen">
                        <option value="">Semua</option>
                        <option value="do" @selected($dokumen === 'do')>Sudah ada DO</option>
                        <option value="inv" @selected($dokumen === 'inv')>Sudah ada Invoice</option>
                        <option value="belum" @selected($dokumen === 'belum')>Belum ada DO/Invoice</option>
                    </select>
                </label>
                --}}
                <button type="submit" class="filter-btn">Tampilkan</button>
                <a href="{{ route('order-documents.index') }}" class="filter-btn secondary">Reset</a>
            </form>
            <p class="filter-note">
                Tanpa pencarian, default transaksi bulan ini. Kosongkan tanggal untuk mencari di semua tanggal.
                @if ($canReprint)
                    Anda dapat mencetak ulang dokumen dari halaman masing-masing.
                @else
                    Cetak ulang hanya melalui {{ \App\Support\CetakUlang::LABEL }}.
                @endif
            </p>
        </div>

        <div style="background:#fff; border:1px solid #d7dee8; border-radius:4px; overflow-x:auto;">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width:44px;">No</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th style="text-align:right;">Total</th>
                        <th>Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $key = $order->order_type.'-'.$order->id;
                            $isVoid = $order->status === 'batal';
                            $soPrintCount = $soPrints[$key] ?? 0;
                        @endphp
                        <tr @class(['is-void' => $isVoid])>
                            <td style="text-align:center;">{{ $orders->firstItem() + $loop->index }}</td>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d/m/Y') }}</td>
                            <td>{{ $order->NmCust ?: ($order->KdCust ?: '-') }}</td>
                            <td style="white-space:nowrap;">
                                @if ($isVoid)
                                    <span class="tag">BATAL</span>
                                @elseif ($order->status_bayar)
                                    <span class="tag {{ $order->status_bayar === 'lunas' ? 'tag-success' : 'tag-warning' }}">{{ strtoupper($order->status_bayar) }}</span>
                                @else
                                    <span class="muted">Belum bayar</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                            <td>
                                <a class="doc-link so" target="_blank" href="{{ route('invoice.show', ['type' => $order->order_type, 'id' => $order->id]) }}">
                                    {{ $order->NoOrder }}
                                    @if ($soPrintCount)<span class="print-count">· cetak {{ $soPrintCount }}×</span>@endif
                                </a>
                                @foreach ($documents[$key] ?? [] as $document)
                                    <a class="doc-link {{ $document->kind }}" target="_blank" href="{{ route('order-documents.show', $document->id) }}">
                                        {{ $document->number }}
                                        @if ($document->print_count)<span class="print-count">· cetak {{ $document->print_count }}×</span>@endif
                                    </a>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px; text-align:center;">Tidak ada transaksi pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ number_format($orders->total(), 0, ',', '.') }} transaksi</span>
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
