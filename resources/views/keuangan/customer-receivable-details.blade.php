<x-app-layout>
    @php
        $reportMode = request('mode') === 'report';
        // Mode bayar (menu Pembayaran Piutang) hanya untuk yang berhak kasir.manage.
        $payMode = ! $reportMode && auth()->user()->hasPermission('kasir.manage');
        $rp = fn ($n) => number_format((float) $n, 0, ',', '.');
        $paymentResult = session('receivable_payment');
    @endphp
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $reportMode ? 'Piutang per Customer' : 'Pembayaran Piutang' }}</h2></x-slot>
    <style>
        .customer-receivable-detail { color:#111827; }
        .customer-receivable-detail table { width:100%; border-collapse:collapse; font-size:12px; }
        .customer-receivable-detail th,.customer-receivable-detail td { border:1px solid #64748b; padding:7px; }
        .customer-receivable-detail th { background:#e2e8f0; text-align:center; }
        .customer-receivable-detail .number { text-align:right; white-space:nowrap; }
        .customer-receivable-detail .pay-table td { padding:5px 7px; vertical-align:middle; }
        .customer-receivable-detail .pay-table tr.is-paying td { background:#eef6ff; }
        .customer-receivable-detail .pay-input { width:100%; min-width:120px; height:32px; padding:0 8px; text-align:right; font-weight:600; font-variant-numeric:tabular-nums; border:1px solid #cbd5e1; border-radius:6px; }
        .customer-receivable-detail .pay-input:focus { outline:2px solid #1b2236; outline-offset:0; border-color:#1b2236; }
        .customer-receivable-detail .pay-input.is-over { border-color:#c8246c; color:#c8246c; background:#fff1f6; }
        .customer-receivable-detail .pay-fill { height:32px; padding:0 8px; font-size:11px; font-weight:600; color:#1b2236; background:#fff; border:1px solid #cbd5e1; border-radius:6px; white-space:nowrap; }
        .customer-receivable-detail .pay-fill:hover { background:#f1ece0; }
        .customer-receivable-detail .pay-seg { display:inline-flex; }
        .customer-receivable-detail .pay-seg label { display:flex; align-items:center; height:36px; padding:0 16px; font-size:13px; font-weight:600; color:#5d5a52; background:#fff; border:1px solid #cfc7b5; border-right:0; cursor:pointer; }
        .customer-receivable-detail .pay-seg label:first-child { border-radius:8px 0 0 8px; }
        .customer-receivable-detail .pay-seg label:last-child { border-right:1px solid #cfc7b5; border-radius:0 8px 8px 0; }
        .customer-receivable-detail .pay-seg label.is-active { color:#fff; background:#1b2236; border-color:#1b2236; }
        .customer-receivable-detail .pay-seg input { display:none; }
        .customer-receivable-detail .pay-summary { min-width:300px; border:1px solid #cfc7b5; border-radius:8px; overflow:hidden; font-size:13px; }
        .customer-receivable-detail .pay-summary div { display:flex; justify-content:space-between; gap:24px; padding:7px 12px; }
        .customer-receivable-detail .pay-summary div + div { border-top:1px dashed #e3ddcf; }
        .customer-receivable-detail .pay-summary b { font-variant-numeric:tabular-nums; }
        @media print {
            body { background:#fff !important; }
            .industry-nav, header { display:none !important; }
            main { padding:0 !important; }
            .customer-receivable-detail { padding:0 !important; }
            .customer-receivable-detail .print-sheet { box-shadow:none !important; padding:0 !important; }
            .no-print { display:none !important; }
        }
    </style>

    <div class="customer-receivable-detail py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            @if (session('status'))<div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <b>Pembayaran belum tersimpan.</b>
                    @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
                </div>
            @endif
            @if ($paymentResult)
                @php($canReport = \App\Support\FinanceMenuAccess::allows(auth()->user(), 'keuangan.rekap-kasir'))
                <div class="no-print mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    <b>Pembayaran tersimpan:</b> {{ $paymentResult['count'] }} nota{{ $paymentResult['settled'] ? ' ('.$paymentResult['settled'].' lunas)' : '' }}
                    · <b>Rp {{ $rp($paymentResult['total']) }}</b> · {{ $paymentResult['method'] }}.
                    <div class="mt-1">Sudah tercatat di jurnal dan di
                        @if ($canReport)
                            <a class="font-semibold underline" href="{{ route('keuangan.rekap-kasir', ['dari' => $paymentResult['date'], 'sampai' => $paymentResult['date']]) }}">Rekap Kas Harian</a>,
                            <a class="font-semibold underline" href="{{ route('keuangan.kas-harian', ['tanggal' => $paymentResult['date']]) }}">Rekap Kasir per User</a> dan
                            <a class="font-semibold underline" href="{{ route('keuangan.laporan-kasir-harian', ['tanggal' => $paymentResult['date']]) }}">Laporan Kasir Harian</a>
                        @else
                            Rekap Kas Harian, Rekap Kasir per User dan Laporan Kasir Harian
                        @endif
                        tanggal {{ \Carbon\Carbon::parse($paymentResult['date'])->format('d-m-Y') }}.
                    </div>
                </div>
            @endif

            <div class="no-print mb-4 rounded-lg border bg-white p-4 shadow-sm">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    @if($reportMode)<input type="hidden" name="mode" value="report">@endif
                    <label class="text-sm text-gray-700">Customer
                        <input type="text" name="customer" list="customer-receivable-options"
                               value="{{ $selectedCustomer?->NmCust ?? request('customer') }}"
                               placeholder="Ketik nama customer..." autocomplete="off" required
                               class="mt-1 block min-w-72 rounded-md border-gray-300">
                        <datalist id="customer-receivable-options">
                            @foreach($customers as $customer)
                                <option value="{{ $customer->NmCust }}">{{ $customer->KdCust }}</option>
                            @endforeach
                        </datalist>
                    </label>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tampilkan</button>
                    @if($reportMode)
                        <button type="button" onclick="window.print()" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Cetak</button>
                    @endif
                </form>
            </div>

            @if ($payMode)
                {{-- Pembayaran Piutang: isi nominal per nota, satu cara bayar, lalu Proses. --}}
                <form method="POST" action="{{ route('keuangan.customer-receivable-payments') }}"
                      x-data="receivablePayment(@js($rows->map(fn ($row) => ['key' => $row->type.'-'.$row->id, 'remaining' => (float) $row->remaining])->values()), @js(old('bayar', [])), @js(old('cara_bayar', 'tunai')))"
                      @submit="confirmSubmit($event)"
                      class="print-sheet bg-white p-5 shadow-sm">
                    @csrf
                    <input type="hidden" name="customer" value="{{ $customerCode }}">
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 class="text-base font-bold">PEMBAYARAN PIUTANG - SPEKTRUM</h1>
                            <p class="text-sm font-semibold">Customer: {{ $selectedCustomer ? $selectedCustomer->NmCust.' ('.$selectedCustomer->KdCust.')' : 'Pilih customer terlebih dahulu' }}</p>
                            <p class="text-sm font-semibold">Tgl. Bayar: {{ now()->format('d-m-Y') }}</p>
                        </div>
                        @if ($rows->isNotEmpty())
                            <div class="flex gap-2">
                                <button type="button" class="pay-fill" @click="fillAll()">Isi semua lunas</button>
                                <button type="button" class="pay-fill" @click="clearAll()">Kosongkan</button>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="pay-table" style="min-width:980px;">
                            <thead><tr><th>No. Order</th><th>No. Nota</th><th>Tgl. Order</th><th>Piutang</th><th>Sudah Dibayar</th><th>Sisa Piutang</th><th style="width:230px;">Pembayaran</th></tr></thead>
                            <tbody>
                                @forelse($rows as $row)
                                    @php($key = $row->type.'-'.$row->id)
                                    <tr :class="amount('{{ $key }}') > 0 && 'is-paying'">
                                        <td>{{ $row->order_number }}</td>
                                        <td>{{ $row->invoice_number ?? '-' }}</td>
                                        <td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
                                        <td class="number">{{ $rp($row->received + $row->remaining) }}</td>
                                        <td class="number">{{ $rp($row->received) }}</td>
                                        <td class="number font-semibold">{{ $rp($row->remaining) }}</td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                <input type="text" inputmode="numeric" name="bayar[{{ $key }}]" class="pay-input" placeholder="0"
                                                       :class="amount('{{ $key }}') > {{ (float) $row->remaining }} && 'is-over'"
                                                       :value="display('{{ $key }}')" @input="setAmount('{{ $key }}', $event.target.value)">
                                                <button type="button" class="pay-fill" @click="fill('{{ $key }}')" title="Isi sebesar sisa piutang">Lunas</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-10 text-center">{{ $selectedCustomer ? 'Customer ini tidak memiliki piutang aktif.' : 'Pilih customer untuk menampilkan seluruh piutang aktif.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($rows->isNotEmpty())
                        <div class="mt-5 flex flex-wrap items-start justify-between gap-5">
                            <div class="space-y-3">
                                <div>
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Cara Bayar</div>
                                    <div class="pay-seg">
                                        @foreach (\App\Services\ReceivableBatchPayment::METHODS as $value => $text)
                                            <label :class="method === '{{ $value }}' && 'is-active'"><input type="radio" name="cara_bayar" value="{{ $value }}" x-model="method">{{ $text }}</label>
                                        @endforeach
                                    </div>
                                </div>
                                <label x-show="method !== 'tunai'" x-cloak class="block text-sm font-medium text-gray-700">No. Referensi
                                    <input type="text" name="no_referensi" maxlength="50" value="{{ old('no_referensi') }}" class="mt-1 block w-72 rounded-md border-gray-300">
                                </label>
                                <label class="block text-sm font-medium text-gray-700">Keterangan <span class="font-normal text-gray-400">(opsional)</span>
                                    <input type="text" name="keterangan" maxlength="150" value="{{ old('keterangan') }}" class="mt-1 block w-72 rounded-md border-gray-300">
                                </label>
                            </div>

                            <div class="space-y-3">
                                <div class="pay-summary">
                                    <div><span>Total Piutang</span><b>{{ $rp($totals->remaining) }}</b></div>
                                    <div style="background:#eef6ff;"><span>Pembayaran (<span x-text="countPaying()"></span> nota)</span><b x-text="rupiah(total())"></b></div>
                                    <div><span>Sisa Piutang</span><b x-text="rupiah({{ (float) $totals->remaining }} - total())"></b></div>
                                </div>
                                <p x-show="hasOver()" x-cloak class="text-sm font-semibold" style="color:#c8246c;">Ada nominal yang melebihi sisa piutang nota.</p>
                                <button type="submit" :disabled="total() <= 0 || hasOver()"
                                        class="w-full rounded-md px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        style="background:#1b2236;">Proses Pembayaran</button>
                            </div>
                        </div>
                    @endif
                </form>

                <script>
                    function receivablePayment(rows, oldAmounts, oldMethod) {
                        const clean = (value) => String(value ?? '').replace(/\D/g, '');
                        return {
                            rows,
                            method: oldMethod || 'tunai',
                            amounts: Object.fromEntries(rows.map((row) => [row.key, clean(oldAmounts[row.key])])),
                            amount(key) { return Number(this.amounts[key] || 0); },
                            display(key) { return this.amounts[key] ? this.amount(key).toLocaleString('id-ID') : ''; },
                            setAmount(key, value) { this.amounts[key] = clean(value); },
                            fill(key) { const row = this.rows.find((r) => r.key === key); this.amounts[key] = String(Math.round(row.remaining)); },
                            fillAll() { this.rows.forEach((row) => this.fill(row.key)); },
                            clearAll() { this.rows.forEach((row) => { this.amounts[row.key] = ''; }); },
                            total() { return this.rows.reduce((sum, row) => sum + this.amount(row.key), 0); },
                            countPaying() { return this.rows.filter((row) => this.amount(row.key) > 0).length; },
                            hasOver() { return this.rows.some((row) => this.amount(row.key) > row.remaining); },
                            rupiah(value) { return Math.round(value).toLocaleString('id-ID'); },
                            confirmSubmit(event) {
                                const label = { tunai: 'Tunai', qris: 'QRIS', transfer: 'Transfer' }[this.method];
                                if (!confirm(`Proses pembayaran Rp ${this.rupiah(this.total())} via ${label} untuk ${this.countPaying()} nota?`)) {
                                    event.preventDefault();
                                }
                            },
                        };
                    }
                </script>
            @else
                <section class="print-sheet bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h1 class="text-base font-bold">{{ $reportMode ? 'PIUTANG PER CUSTOMER' : 'PEMBAYARAN PIUTANG' }} - SPEKTRUM</h1>
                        <p class="text-sm font-semibold">Posisi per: {{ now()->translatedFormat('d F Y') }} (semua piutang belum lunas)</p>
                        <p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table style="min-width:980px;">
                            <thead><tr><th>Tanggal</th><th>No. Nota</th><th>Piutang</th><th>Discount</th><th>Bayar</th><th>Sisa Piutang</th></tr></thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->invoice }}</td>
                                        <td class="number">{{ number_format($row->receivable,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->paid,0,',','.') }}</td><td class="number font-semibold">{{ number_format($row->remaining,0,',','.') }}</td>
                                    </tr>
                                @empty<tr><td colspan="6" class="py-10 text-center">{{ $selectedCustomer ? 'Customer ini tidak memiliki piutang aktif.' : 'Pilih customer untuk menampilkan seluruh piutang aktif.' }}</td></tr>@endforelse
                            </tbody>
                            <tfoot><tr><td colspan="2" class="number"><strong>Total Piutang</strong></td><td class="number"><strong>{{ number_format($totals->receivable,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->discount,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->paid,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->remaining,0,',','.') }}</strong></td></tr></tfoot>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
