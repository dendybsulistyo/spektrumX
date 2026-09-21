<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Customer Service</h2>
            <p class="mt-1 text-sm text-slate-500">Catat informasi transfer sebelum order diteruskan ke Kasir.</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Nomor order</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total order</th>
                        <th class="px-4 py-3">Nominal transfer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $order->TglOrder?->format('d/m/Y') ?? $order->TglOrder }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-4"><span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ ucfirst($order->order_type) }}</span></td>
                            <td class="px-4 py-4 text-slate-700">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-medium text-slate-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td colspan="3" class="px-4 py-3">
                                <form method="POST" action="{{ route('customer-service.forward', ['type' => $order->order_type, 'id' => $order->id]) }}"
                                      class="grid grid-cols-[minmax(180px,1fr)_150px_auto] gap-2"
                                      x-data="{ raw: '', display: '', total: {{ (float) $order->total }}, format(event) { this.raw = event.target.value.replace(/\D/g, ''); this.display = this.raw ? 'Rp ' + Number(this.raw).toLocaleString('id-ID') : ''; } }">
                                    @csrf
                                    <div>
                                        <input type="text" inputmode="numeric" x-model="display" @input="format($event)" placeholder="Rp 0"
                                               class="w-full rounded border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <input type="hidden" name="cs_transfer_amount" :value="raw">
                                    </div>
                                    <div class="flex items-center">
                                        <span x-show="!raw" class="text-xs text-slate-400">Otomatis</span>
                                        <span x-show="raw" x-text="Number(raw) < total ? 'DP' : (Number(raw) === total ? 'Pelunasan' : 'Melebihi total')"
                                              :class="Number(raw) > total ? 'bg-red-100 text-red-700' : (Number(raw) < total ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')"
                                              class="rounded px-2.5 py-1 text-xs font-semibold"></span>
                                    </div>
                                    <button type="submit" class="whitespace-nowrap rounded bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">Kirim ke Kasir</button>
                                </form>
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
