<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Customer Service</h2>
            <p class="mt-1 text-sm text-slate-500">Catat transfer sebelum order diteruskan ke Kasir.</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    {{-- <div class="mb-2">
        <h3 class="font-semibold text-slate-900">Antrean Pembayaran</h3>
        <p class="mt-0.5 text-xs text-slate-500">Order yang perlu dilengkapi informasinya dan diteruskan ke Kasir.</p>
    </div> --}}

    <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Nomor Order</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total Order</th>
                        <th class="px-4 py-3">Nominal Transfer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        @php $minimumTransfer = ceil(((float) $order->total * 0.5) / 100) * 100; @endphp
                        <tr class="align-middle hover:bg-slate-50/70"
                            x-data="{ mode: 'payment', raw: '', display: '', total: {{ (float) $order->total }}, minimum: {{ (float) $minimumTransfer }}, customArtwork: @js((bool) $order->is_custom_artwork), format(event) { this.raw = event.target.value.replace(/\D/g, ''); this.display = this.raw ? 'Rp ' + Number(this.raw).toLocaleString('id-ID') : ''; } }">
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $order->TglOrder?->format('d/m/Y') ?? $order->TglOrder }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-4"><span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($order->order_type) }}</span></td>
                            <td class="px-4 py-4 text-slate-700">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-medium text-slate-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="w-56 px-4 py-3">

                                @if ($order->is_custom_artwork)
                                    <div class="mb-3 space-y-2 rounded border border-violet-200 bg-violet-50 p-2">
                                        <p class="text-[11px] font-semibold text-violet-800">Harga Custom Artwork / unit</p>
                                        @foreach ($order->items->filter(fn ($item) => $item->requires_custom_price) as $item)
                                            <label class="block text-[11px] text-violet-700">
                                                {{ $item->Judul ?: $item->NmProd }} · Qty {{ $item->Qty }}
                                                <input type="number" min="100" step="100" required
                                                       name="artwork_prices[{{ $item->id }}]"
                                                       value="{{ (float) $item->harga_satuan_kasir > 0 ? (int) $item->harga_satuan_kasir : '' }}"
                                                       form="cs-payment-{{ $order->order_type }}-{{ $order->id }}"
                                                       placeholder="Harga per unit"
                                                       class="mt-1 block w-full rounded border-violet-300 py-1.5 text-xs focus:border-violet-500 focus:ring-violet-500">
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                <select x-model="mode" class="mb-2 w-52 rounded border-slate-300 py-1.5 text-xs focus:border-blue-500 focus:ring-blue-500">
                                    <option value="payment">DP / Pelunasan</option>
                                    <option value="debt" @disabled(! $order->customer?->isVip)>Hutang{{ $order->customer?->isVip ? '' : ' — khusus VIP' }}</option>
                                </select>

                                <input x-show="mode === 'payment'" type="text" inputmode="numeric" x-model="display" @input="format($event)" placeholder="Rp 0"
                                       form="cs-payment-{{ $order->order_type }}-{{ $order->id }}"
                                       class="w-52 rounded border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="hidden" name="cs_transfer_amount" :value="raw" form="cs-payment-{{ $order->order_type }}-{{ $order->id }}">
                                <input type="hidden" name="cs_payment_type" :value="mode === 'debt' ? 'hutang' : ''" form="cs-payment-{{ $order->order_type }}-{{ $order->id }}">
                                <p x-show="mode === 'payment'" class="mt-1 text-[11px] text-slate-400">Minimal 50%: Rp {{ number_format($minimumTransfer, 0, ',', '.') }}{{ $order->is_custom_artwork ? ' · Harga Artwork custom' : '' }}</p>
                                <p x-show="mode === 'debt'" x-cloak class="mt-1 text-[11px] text-amber-600">Nilai penuh akan diajukan sebagai piutang.</p>
                            </td>
                            <td class="w-36 px-4 py-3">
                                <span x-show="mode === 'payment' && !raw" class="text-xs text-slate-400">Otomatis</span>
                                <span x-show="mode === 'payment' && raw" x-text="Number(raw) < minimum ? 'Di bawah minimum' : (customArtwork && Number(raw) > total ? 'Harga Custom' : (Number(raw) < total ? 'DP' : (Number(raw) === total ? 'Pelunasan' : 'Melebihi total')))"
                                      :class="Number(raw) < minimum || (!customArtwork && Number(raw) > total) ? 'bg-red-100 text-red-700' : (customArtwork && Number(raw) > total ? 'bg-violet-100 text-violet-700' : (Number(raw) < total ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'))"
                                      class="rounded px-2.5 py-1 text-xs font-semibold"></span>
                                <span x-show="mode === 'debt'" x-cloak class="rounded bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">Hutang</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($order->is_custom_artwork)
                                    <button type="submit" form="cs-payment-{{ $order->order_type }}-{{ $order->id }}"
                                            formaction="{{ route('customer-service.artwork-prices.save', $order->id) }}" formtarget="_blank"
                                            class="mr-2 inline-flex rounded border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                                        Simpan Harga &amp; Draft SO
                                    </button>
                                @else
                                    <a href="{{ route('invoice.show', ['type' => $order->order_type, 'id' => $order->id, 'source' => 'cs', 'draft' => 1]) }}"
                                       target="_blank" rel="noopener"
                                       class="mr-2 inline-flex rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Draft SO
                                    </a>
                                @endif
                                <form id="cs-payment-{{ $order->order_type }}-{{ $order->id }}" method="POST"
                                      action="{{ route('customer-service.forward', ['type' => $order->order_type, 'id' => $order->id]) }}">
                                    @csrf
                                </form>
                                <button type="submit" form="cs-payment-{{ $order->order_type }}-{{ $order->id }}"
                                        class="rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                    Kirim Kasir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-14 text-center text-slate-400">Belum ada order yang dikirim ke Customer Service.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
