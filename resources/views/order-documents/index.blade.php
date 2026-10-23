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
        </style>
    @endpush

    <div id="order-document-archive" style="max-width:1240px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
        <div class="filter-panel" style="padding:16px; background:#fff; border:1px solid #d7dee8; border-radius:4px;">
            <form method="GET" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                <div class="field" style="width:260px;">
                    <label>Cari</label>
                    <input name="search" value="{{ $search }}" placeholder="No. SO / DO / Invoice / customer" class="input">
                </div>
                <div class="field" style="width:160px;">
                    <label>Dari tanggal</label>
                    <input type="date" name="dari" value="{{ $dari }}" class="input">
                </div>
                <div class="field" style="width:160px;">
                    <label>Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ $sampai }}" class="input">
                </div>
                <div class="field" style="width:140px;">
                    <label>Status bayar</label>
                    <select name="status" class="input">
                        <option value="">Semua</option>
                        @foreach (['lunas' => 'Lunas', 'dp' => 'DP', 'hutang' => 'Hutang', 'batal' => 'Batal'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="width:170px;">
                    <label>Dokumen</label>
                    <select name="dokumen" class="input">
                        <option value="">Semua</option>
                        <option value="do" @selected($dokumen === 'do')>Sudah ada DO</option>
                        <option value="inv" @selected($dokumen === 'inv')>Sudah ada Invoice</option>
                        <option value="belum" @selected($dokumen === 'belum')>Belum ada DO/Invoice</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height:36px;">Tampilkan</button>
                <a href="{{ route('order-documents.index') }}" class="btn btn-secondary" style="height:36px;">Reset</a>
            </form>
            <p class="muted" style="margin:10px 0 0; font-size:12px;">
                Pencarian berlaku untuk semua tanggal bila tanggal dikosongkan.
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
                        <tr><td colspan="5" class="muted" style="padding:24px; text-align:center;">Tidak ada transaksi pada filter ini.</td></tr>
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
