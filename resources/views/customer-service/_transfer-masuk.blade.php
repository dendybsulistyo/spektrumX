{{-- Tab Transfer Masuk: transfer dari customer yang belum jelas untuk order mana. --}}
<div x-show="tab === 'transfer'" @if ($initialTab !== 'transfer') x-cloak @endif>
    @can('customer-service.manage')
        <form method="POST" action="{{ route('customer-service.transfer-masuk.store') }}"
              class="grid gap-3 border-b border-slate-200 bg-slate-50/60 p-4 text-sm md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.5fr)_auto] md:items-end"
              x-data="{
                query: @js(old('customer_label', '')),
                selectedCode: @js(old('customer_code', '')),
                results: [],
                open: false,
                timer: null,
                amount: @js(old('amount') ? number_format((int) preg_replace('/\D/', '', old('amount')), 0, ',', '.') : ''),
                search() {
                    this.selectedCode = '';
                    clearTimeout(this.timer);
                    if (this.query.trim().length < 2) { this.results = []; this.open = false; return; }
                    this.timer = setTimeout(async () => {
                        const response = await fetch(`/customers-search?cs=1&q=${encodeURIComponent(this.query.trim())}`, { headers: { 'Accept': 'application/json' } });
                        this.results = response.ok ? await response.json() : [];
                        this.open = true;
                    }, 300);
                },
                select(customer) {
                    this.selectedCode = customer.KdCust;
                    this.query = `${customer.NmCust} (${customer.KdCust})`;
                    this.open = false;
                },
                formatAmount() {
                    const digits = this.amount.replace(/\D/g, '');
                    this.amount = digits ? Number(digits).toLocaleString('id-ID') : '';
                },
              }">
            @csrf
            <div class="relative" @click.outside="open = false">
                <label for="tm-customer" class="text-xs font-semibold text-slate-600">Nama Customer</label>
                <input id="tm-customer" type="text" name="customer_label" x-model="query" @input="search()" autocomplete="off" required
                       placeholder="Ketik min. 2 huruf nama / kode..." class="mt-1 w-full rounded border-slate-300 text-sm">
                <input type="hidden" name="customer_code" :value="selectedCode">
                <div x-show="open && results.length" x-cloak
                     class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded border border-slate-200 bg-white py-1 shadow-xl">
                    <template x-for="customer in results" :key="customer.KdCust">
                        <button type="button" @click="select(customer)" class="block w-full px-3 py-2 text-left text-slate-700 hover:bg-indigo-50">
                            <span class="font-medium" x-text="customer.NmCust"></span>
                            <span class="ml-1 text-xs text-slate-400" x-text="`(${customer.KdCust})`"></span>
                            <template x-for="order in customer.cs_orders || []" :key="order.key">
                                <span class="mt-0.5 block font-mono text-[11px] text-slate-500"
                                      x-text="`${order.no} · Rp ${Number(order.total).toLocaleString('id-ID')} · ${order.date ? new Date(order.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-'}`"></span>
                            </template>
                            <span x-show="!(customer.cs_orders || []).length" class="mt-0.5 block text-[11px] text-slate-400">Tidak ada order CS yang belum dibayar.</span>
                        </button>
                    </template>
                </div>
                <p x-show="open && !results.length" x-cloak class="mt-1 text-[11px] text-slate-500">Customer tidak ditemukan.</p>
                <p x-show="query && !selectedCode && !open" x-cloak class="mt-1 text-[11px] text-amber-600">Pilih customer dari hasil pencarian.</p>
            </div>
            <div>
                <label for="tm-amount" class="text-xs font-semibold text-slate-600">Jumlah Transfer (Rp)</label>
                <input id="tm-amount" type="text" inputmode="numeric" name="amount" x-model="amount" @input="formatAmount()" required
                       placeholder="0" class="mt-1 w-full rounded border-slate-300 text-right text-sm">
            </div>
            <div>
                <label for="tm-date" class="text-xs font-semibold text-slate-600">Tanggal Transfer</label>
                <input id="tm-date" type="date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required
                       class="mt-1 w-full rounded border-slate-300 text-sm">
            </div>
            <div>
                <label for="tm-notes" class="text-xs font-semibold text-slate-600">Catatan</label>
                <input id="tm-notes" type="text" name="notes" value="{{ old('notes') }}" maxlength="255"
                       placeholder="Opsional, mis. via BCA" class="mt-1 w-full rounded border-slate-300 text-sm">
            </div>
            <button type="submit" :disabled="!selectedCode" class="h-[42px] rounded bg-indigo-600 px-5 font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                Simpan
            </button>
        </form>
    @endcan

    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">Tgl Transfer</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2 text-right">Jumlah</th>
                    <th class="px-3 py-2">Catatan</th>
                    <th class="px-3 py-2">Dicatat</th>
                    @can('customer-service.manage')<th class="px-3 py-2"></th>@endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transfers as $transfer)
                    <tr class="align-middle hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-3 py-2">{{ $transfer->transfer_date->translatedFormat('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-900">{{ $transfer->customer_name }}</div>
                            <div class="text-xs text-slate-400">{{ $transfer->customer_code }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-semibold">{{ number_format((float) $transfer->amount, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $transfer->notes ?: '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-500">
                            {{ $transfer->creator?->name ?? '-' }}<br>{{ $transfer->created_at->format('d/m H:i') }}
                        </td>
                        @can('customer-service.manage')
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                @php($options = $transferOrders->get(trim($transfer->customer_code), collect()))
                                <div class="inline" x-data="{ open: false, order: @js(optional($options->firstWhere('custom', false))['key'] ?? '') }">
                                    <button type="button" @click="open = true" class="h-8 rounded bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700">Sudah dipakai</button>
                                    <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4 text-left" @keydown.escape.window="open = false">
                                        <form method="POST" action="{{ route('customer-service.transfer-masuk.use', $transfer) }}" @click.outside="open = false"
                                              class="w-full max-w-md whitespace-normal rounded-lg bg-white shadow-xl">
                                            @csrf
                                            <div class="border-b px-5 py-4">
                                                <h3 class="font-semibold text-slate-900">Transfer dipakai untuk order apa?</h3>
                                                <p class="text-xs text-slate-500">{{ $transfer->customer_name }} · Rp {{ number_format((float) $transfer->amount, 0, ',', '.') }}</p>
                                            </div>
                                            <div class="max-h-72 space-y-1 overflow-y-auto px-5 py-3 text-sm">
                                                @foreach ($options as $option)
                                                    <label @class(['flex items-center gap-2 rounded border px-3 py-2', 'cursor-pointer hover:bg-emerald-50' => ! $option['custom'], 'opacity-60' => $option['custom']])>
                                                        <input type="radio" name="order" value="{{ $option['key'] }}" x-model="order" @disabled($option['custom'])>
                                                        <span class="flex-1">
                                                            <span class="font-mono font-semibold">{{ $option['no'] }}</span>
                                                            <span class="block text-xs text-slate-500">
                                                                {{ ucfirst($option['type']) }} · total Rp {{ number_format($option['total'], 0, ',', '.') }}
                                                                · {{ $option['date'] ? \Illuminate\Support\Carbon::parse($option['date'])->translatedFormat('d M') : '-' }}
                                                                @if ($option['custom'])<span class="text-amber-700">· Artwork custom, teruskan lewat tab Indoor</span>@endif
                                                            </span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                                <label class="flex cursor-pointer items-center gap-2 rounded border px-3 py-2 hover:bg-slate-50">
                                                    <input type="radio" name="order" value="" x-model="order">
                                                    <span class="flex-1">Order lain / tidak di antrean CS</span>
                                                </label>
                                                <input x-show="order === ''" type="text" name="used_order_number" maxlength="40" placeholder="Nomor order (opsional)"
                                                       class="mt-1 w-full rounded border-slate-300 text-sm">
                                                <p x-show="order !== ''" class="pt-1 text-xs text-slate-500">Order terpilih langsung diteruskan ke Kasir dengan nominal transfer ini (minimal 50% dari total, tidak melebihi total).</p>
                                            </div>
                                            <div class="flex justify-end gap-2 border-t px-5 py-3">
                                                <button type="button" @click="open = false" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Batal</button>
                                                <button class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                                        x-text="order !== '' ? 'Teruskan ke Kasir' : 'Tandai dipakai'"></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('customer-service.transfer-masuk.destroy', $transfer) }}" class="inline"
                                      onsubmit="return confirm('Hapus transfer ' + {{ \Illuminate\Support\Js::from($transfer->customer_name.' Rp '.number_format((float) $transfer->amount, 0, ',', '.')) }} + '?\nData akan dihapus permanen.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-1 h-8 rounded bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700">Hapus</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-slate-400">Belum ada transfer masuk yang perlu dicocokkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
