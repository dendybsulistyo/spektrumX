<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Bayar Order {{ $order->NoOrder }}</h2>
    </x-slot>

    <div x-data="{ invoiceModalOpen: false, autoPrintPending: {{ session('autoPrintInvoice') ? 'true' : 'false' }}, diskonModalOpen: false, cancelModalOpen: false, batalOrderModalOpen: false }"
         x-init="if (autoPrintPending) { invoiceModalOpen = true }"
         @keydown.escape.window="invoiceModalOpen = false; diskonModalOpen = false; cancelModalOpen = false; batalOrderModalOpen = false">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 overflow-hidden">
            @php $diskonStatus = $order->diskonStatus(); @endphp

            @if ($order->replaces)
                @php
                    $credit = (float) $order->replacement_credit;
                    // Must match what actually gets charged (KasirController::bayar()
                    // uses totalSetelahDiskon() once a diskon is approved) — otherwise
                    // this banner disagrees with the "Total setelah diskon" shown below.
                    $totalBaru = $diskonStatus === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
                    $difference = $totalBaru - $credit;
                @endphp
                <div class="border-b border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Nota pengganti dari nota hangus {{ $order->replaces->NoOrder }}</p>
                    <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-3">
                        <span>Kredit lama: Rp {{ number_format($credit, 0, ',', '.') }}</span>
                        <span>Nilai Order: Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                        @if ($diskonStatus === 'approved')
                            <span>Diskon {{ $order->diskonApprovedLabel() }}: -Rp {{ number_format($order->diskonNominal(), 0, ',', '.') }}</span>
                        @else
                            <span></span>
                        @endif
                    </div>
                    <div class="mt-1 grid grid-cols-1 gap-1 sm:grid-cols-3">
                        @if ($diskonStatus === 'approved')
                            <span>Total setelah diskon: Rp {{ number_format($totalBaru, 0, ',', '.') }}</span>
                        @endif
                        <span class="font-semibold sm:col-start-3">{{ $difference > 0 ? 'Tambahan: Rp '.number_format($difference, 0, ',', '.') : ($difference < 0 ? 'Cashback: Rp '.number_format(abs($difference), 0, ',', '.') : 'Tidak ada selisih') }}</span>
                    </div>
                </div>
            @endif
            <div class="p-4 border-b border-gray-200">
                <p class="text-sm text-gray-500">Customer</p>
                <p class="font-semibold text-gray-900">{{ $order->customer?->NmCust ?? '-' }}</p>
                @if ($order->customer?->isVip)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 mt-1">VIP</span>
                    @if ($order->withinHutangPlafon())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 mt-1 ml-1">Dalam plafon hutang</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 mt-1 ml-1">Melebihi plafon hutang</span>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">
                        Limit: Rp {{ number_format($order->customer->limit->Batas, 0, ',', '.') }} —
                        Piutang berjalan: Rp {{ number_format($order->customer->limit->Total, 0, ',', '.') }} —
                        Sisa Plafon: Rp {{ number_format($order->customer->limit->Batas - $order->customer->limit->Total, 0, ',', '.') }}
                    </p>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-[13px] min-w-[480px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Item</th>
                            <th class="px-3 py-2">Bahan</th>
                            <th class="px-3 py-2">Printer</th>
                            <th class="px-3 py-2 text-right">Pj</th>
                            <th class="px-3 py-2 text-right">Lb</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-right">Hrg. Satuan</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-900">
                                    {{ $item->name }}
                                    @if ($item->breakdown)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $item->breakdown }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600">{{ $item->bahan ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $item->printer ?? '-' }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $item->panjang }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $item->lebar }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $item->qty }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $item->harga_satuan !== null ? 'Rp '.number_format($item->harga_satuan, 0, ',', '.') : '-' }}</td>
                                <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700">Total</span>
                    <span class="text-lg font-bold text-gray-900 {{ $diskonStatus === 'approved' ? 'line-through text-gray-400 text-base font-normal' : '' }}">
                        Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                @if ($diskonStatus === 'approved')
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-sm text-red-600">Diskon {{ $order->diskonApprovedLabel() }}</span>
                        <span class="text-sm text-red-600">- Rp {{ number_format($order->diskonNominal(), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-sm font-semibold text-green-700">Total setelah diskon {{ $order->diskonApprovedLabel() }}</span>
                        <span class="text-lg font-bold text-green-700">Rp {{ number_format($order->totalSetelahDiskon(), 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="mt-3">
                    @if ($diskonStatus === 'none')
                        @can('kasir.manage')
                            @if ($order->status_bayar !== 'lunas')
                                <button type="button" @click="diskonModalOpen = true"
                                        class="text-xs font-semibold text-blue-600 hover:underline">
                                    + Ajukan Diskon
                                </button>
                            @endif
                        @endcan
                    @elseif ($diskonStatus === 'pending')
                        <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                            <p class="font-semibold">Menunggu persetujuan diskon {{ $order->diskonRequestedLabel() }}</p>
                            <p class="mt-0.5">Alasan: {{ $order->diskon_alasan }}</p>
                            <p class="mt-0.5 text-amber-600">Diajukan oleh {{ $order->diskonRequestedBy?->name ?? '-' }}</p>
                            @can('kasir.approve-diskon')
                                <div class="mt-2 flex gap-2">
                                    <form method="POST" action="{{ route('kasir.diskon.approve', ['type' => $type, 'id' => $order->id]) }}"
                                          onsubmit="return confirm('Setujui diskon {{ $order->diskonRequestedLabel() }} untuk order {{ $order->NoOrder }}?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-green-600 text-white text-xs font-semibold rounded hover:bg-green-700">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('kasir.diskon.reject', ['type' => $type, 'id' => $order->id]) }}"
                                          onsubmit="return confirm('Tolak pengajuan diskon untuk order {{ $order->NoOrder }}?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-gray-200 text-gray-700 text-xs font-semibold rounded hover:bg-gray-300">Tolak</button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @elseif ($diskonStatus === 'rejected')
                        <div class="rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
                            <p>Pengajuan diskon {{ $order->diskonRequestedLabel() }} ditolak oleh {{ $order->diskonRejectedBy?->name ?? '-' }}.</p>
                            @can('kasir.manage')
                                @if ($order->status_bayar !== 'lunas')
                                    <button type="button" @click="diskonModalOpen = true" class="mt-1 font-semibold text-blue-600 hover:underline">
                                        Ajukan lagi
                                    </button>
                                @endif
                            @endcan
                        </div>
                    @endif
                </div>

                @if ($order->hutangApprovalStatus() === 'pending')
                    <div class="mt-3 rounded-md bg-red-50 border border-red-200 p-3 text-xs text-red-800">
                        <p class="font-semibold">Menunggu persetujuan hutang — melebihi plafon (Rp {{ number_format($order->hutangAmount(), 0, ',', '.') }})</p>
                        @if ($order->hutang_catatan)
                            <p class="mt-0.5">Catatan: {{ $order->hutang_catatan }}</p>
                        @endif
                        <p class="mt-0.5 text-red-600">Diajukan oleh {{ $order->hutangRequestedBy?->name ?? '-' }}</p>
                        @can('kasir.approve-hutang')
                            <div class="mt-2 flex gap-2">
                                <form method="POST" action="{{ route('kasir.hutang.approve', ['type' => $type, 'id' => $order->id]) }}"
                                      onsubmit="return confirm('Setujui hutang Rp {{ number_format($order->hutangAmount(), 0, ',', '.') }} untuk order {{ $order->NoOrder }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-green-600 text-white text-xs font-semibold rounded hover:bg-green-700">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('kasir.hutang.reject', ['type' => $type, 'id' => $order->id]) }}"
                                      onsubmit="return confirm('Tolak pengajuan hutang untuk order {{ $order->NoOrder }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-gray-200 text-gray-700 text-xs font-semibold rounded hover:bg-gray-300">Tolak</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                @elseif ($order->hutangApprovalStatus() === 'rejected')
                    <div class="mt-3 rounded-md bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
                        <p>Pengajuan hutang ditolak oleh {{ $order->hutangRejectedBy?->name ?? '-' }}. Pilih metode pembayaran lain, atau ajukan hutang lagi jika situasinya sudah berubah.</p>
                    </div>
                @endif

                @if ($order->cancel_requested_at)
                    <div class="mt-3 rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                        <p class="font-semibold">Menunggu persetujuan pembatalan</p>
                        <p class="mt-0.5">Alasan: {{ $order->cancel_reason }}</p>
                        <p class="mt-0.5 text-amber-600">Diajukan oleh {{ $order->cancelRequestedBy?->name ?? '-' }} — disetujui/ditolak dari Antrian Desain.</p>
                    </div>
                @elseif ($order->status === 'desain')
                    @can('kasir.manage')
                        @if ($pendingRework)
                            <div class="mt-3 rounded-md bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                                Ada pengajuan Ulang Proses / Batalkan Order yang masih menunggu persetujuan untuk order ini.
                            </div>
                        @else
                            <div class="mt-3 flex gap-2">
                                <button type="button" @click="cancelModalOpen = true"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                                    Batal &amp; IB
                                </button>
                                <button type="button" @click="batalOrderModalOpen = true"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 border border-red-300 text-xs font-semibold rounded-md hover:bg-red-100">
                                    Batalkan Order
                                </button>
                            </div>
                        @endif
                    @endcan
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4"
             x-data="{
                isReplacement: {{ $order->replacement_order_id ? 'true' : 'false' }},
                metode: '{{ old('metode_bayar', 'tunai') }}',
                caraBayar: '{{ old('cara_bayar', 'tunai') }}',
                noReferensi: '{{ old('no_referensi') }}',
                jumlahBayarReplacement: '{{ old('jumlah_bayar') }}',
                replacementTarget: {{ (float) ($difference ?? 0) }},
                get replacementKembalian() {
                    if (!this.jumlahBayarReplacement) return 0;
                    return Math.max(0, Math.round((Number(this.jumlahBayarReplacement) - this.replacementTarget) * 100) / 100);
                },
                get replacementError() {
                    if (!this.isReplacement || this.replacementTarget <= 0) return '';
                    const val = Number(this.jumlahBayarReplacement || 0);
                    if (val + 0.5 < this.replacementTarget) return `Kurang Rp ${Math.round(this.replacementTarget - val).toLocaleString('id-ID')}.`;
                    return '';
                },
                dpMin: {{ (int) (ceil((($diskonStatus === 'approved' ? $order->totalSetelahDiskon() : $order->total ?? 0) * 0.5) / 100) * 100) }},
                dpMax: {{ max((int) (round(($diskonStatus === 'approved' ? $order->totalSetelahDiskon() : $order->total ?? 0) / 100) * 100) - 100, 0) }},
                // DP no longer has its own 'Jumlah DP' input — that was the
                // same amount typed twice, once here and once in the rincian
                // row below. The rincian total below now IS the DP amount.
                get dpError() {
                    if (this.metode !== 'dp' || this.rincianTotal === 0) return '';
                    if (this.rincianTotal < this.dpMin) return `Jumlah DP minimal Rp ${this.dpMin.toLocaleString('id-ID')}.`;
                    if (this.rincianTotal > this.dpMax) return `Jumlah DP tidak boleh melebihi Rp ${this.dpMax.toLocaleString('id-ID')}.`;
                    return '';
                },
                rincian: [{ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }],
                totalBayar: {{ (float) (round(($diskonStatus === 'approved' ? $order->totalSetelahDiskon() : ($order->total ?? 0)) / 100) * 100) }},
                get rincianTotal() {
                    return this.rincian.reduce((sum, r) => sum + Number(r.jumlah || 0), 0);
                },
                // DP has no fixed target to match — the operator types
                // whatever the customer is paying now and dpError checks it
                // against the 50% minimum instead. Only 'lunas' (fixed-price)
                // payment must add up to the exact total.
                get rincianDiff() {
                    if (this.metode === 'dp') return 0;
                    return Math.round((this.totalBayar - this.rincianTotal) * 100) / 100;
                },
                // POS-style: paying more than the bill is fine — the excess
                // is change handed back, not an error.
                get rincianKembalian() {
                    return this.rincianDiff < 0 ? Math.abs(this.rincianDiff) : 0;
                },
                get rincianError() {
                    // Nota pengganti doesn't use the rincian split-payment
                    // UI at all (still the old single cara_bayar radios
                    // below) — nothing to validate here, or Proses
                    // Pembayaran would stay permanently blocked for it.
                    if (this.isReplacement || this.metode === 'hutang') return '';
                    if (this.rincian.some((row) => Number(row.jumlah || 0) % 100 !== 0)) return 'Setiap nominal pembayaran harus kelipatan Rp100.';
                    if (this.metode === 'dp' || this.rincianDiff <= 0) return '';
                    return `Kurang Rp ${this.rincianDiff.toLocaleString('id-ID')}.`;
                },
                addRincian() { this.rincian.push({ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }); },
                removeRincian(i) { this.rincian.splice(i, 1); },
             }">
            <form method="POST" action="{{ route('kasir.bayar', ['type' => $type, 'id' => $order->id]) }}"
                  class="space-y-4" novalidate
                  @submit="if (dpError || rincianError || replacementError) { $event.preventDefault(); }">
                @csrf

                    <div>
                    @if (! $order->replacement_order_id)
                    <x-input-label value="Metode Pembayaran" />
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="tunai" x-model="metode" class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Lunas</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="hutang" x-model="metode"
                                   {{ $order->customer?->isVip ? '' : 'disabled' }}
                                   class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700 {{ $order->customer?->isVip ? '' : 'text-gray-400' }}">
                                Hutang (khusus VIP){{ $order->customer?->isVip ? '' : ' — customer bukan VIP' }}
                            </span>
                        </label>
                        @if ($type === 'outdoor')
                            <label class="flex items-center gap-2">
                                <input type="radio" name="metode_bayar" value="dp" x-model="metode" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">DP (minimal 50%)</span>
                            </label>
                        @endif
                    </div>
                    @else
                        <input type="hidden" name="metode_bayar" value="tunai">
                        <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-800">Proses nota pengganti akan mencatat selisih sebagai tambahan pembayaran atau cashback.</p>
                    @endif
                    <x-input-error :messages="$errors->get('metode_bayar')" class="mt-1" />
                </div>

                @if ($order->replacement_order_id)
                    <div x-show="metode !== 'hutang'" x-cloak>
                        <x-input-label value="Cara Bayar" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="tunai" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Tunai</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="qris" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">QRIS</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="transfer" x-model="caraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Transfer</span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('cara_bayar')" class="mt-1" />
                    </div>

                    <div x-show="metode !== 'hutang' && caraBayar !== 'tunai'" x-cloak>
                        <x-input-label for="no_referensi" value="No. Referensi" />
                        <x-text-input id="no_referensi" name="no_referensi" type="text" x-model="noReferensi"
                                      class="mt-1 block w-full" maxlength="50" placeholder="ID transaksi QRIS / 4 digit terakhir rekening tujuan" />
                        <x-input-error :messages="$errors->get('no_referensi')" class="mt-1" />
                    </div>

                    @if (($difference ?? 0) > 0)
                        <div x-show="metode !== 'hutang'" x-cloak>
                            <x-input-label value="Jumlah Bayar" />
                            <input type="text" inputmode="numeric"
                                   :value="jumlahBayarReplacement ? Number(jumlahBayarReplacement).toLocaleString('id-ID') : ''"
                                   @input="jumlahBayarReplacement = $event.target.value.replace(/\D/g, '')"
                                   placeholder="Jumlah" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <input type="hidden" name="jumlah_bayar" :value="jumlahBayarReplacement">
                            <p class="text-xs mt-1" :class="replacementError ? 'text-red-600 font-semibold' : (replacementKembalian > 0 ? 'text-green-600 font-semibold' : 'text-gray-400')">
                                Tambahan yang harus dibayar: Rp {{ number_format($difference, 0, ',', '.') }}
                                <span x-show="replacementError" x-text="'— ' + replacementError"></span>
                                <span x-show="replacementKembalian > 0" x-text="'— Kembalian: Rp ' + replacementKembalian.toLocaleString('id-ID')"></span>
                            </p>
                            <x-input-error :messages="$errors->get('jumlah_bayar')" class="mt-1" />
                        </div>
                    @endif
                @else
                    <div x-show="metode !== 'hutang'" x-cloak>
                        <x-input-label value="Rincian Pembayaran" />
                        <p class="text-xs text-gray-500 mt-0.5" x-show="metode !== 'dp'">Bisa dibagi ke beberapa metode sekaligus, misalnya sebagian QRIS sebagian transfer.</p>
                        @php
                            $dpBasis = $diskonStatus === 'approved' ? $order->totalSetelahDiskon() : ($order->total ?? 0);
                            $minimumDp = ceil(($dpBasis * 0.5) / 100) * 100;
                        @endphp
                        <p class="text-xs text-gray-500 mt-0.5" x-show="metode === 'dp'" x-cloak>
                            Jumlah yang diisi di bawah menjadi jumlah DP — minimal Rp {{ number_format($minimumDp, 0, ',', '.') }} (50% dari total {{ $diskonStatus === 'approved' ? 'setelah diskon ' : '' }}Rp {{ number_format($dpBasis, 0, ',', '.') }}, dibulatkan ke atas per Rp100).
                        </p>

                        <div class="mt-2 space-y-2">
                            <template x-for="(row, idx) in rincian" :key="idx">
                                <div class="flex items-start gap-2 rounded-md border border-gray-200 p-2">
                                    <div class="flex-1 space-y-1.5">
                                        <div class="flex gap-2">
                                            <select :name="`rincian[${idx}][cara_bayar]`" x-model="row.cara_bayar" :disabled="metode === 'hutang'"
                                                    class="rounded-md border-gray-300 text-sm py-1.5 w-28">
                                                <option value="tunai">Tunai</option>
                                                <option value="qris">QRIS</option>
                                                <option value="transfer">Transfer</option>
                                            </select>
                                            <input type="text" inputmode="numeric"
                                                   :value="row.jumlah ? Number(row.jumlah).toLocaleString('id-ID') : ''"
                                                   @input="row.jumlah = $event.target.value.replace(/\D/g, '')"
                                                   placeholder="Jumlah" class="flex-1 rounded-md border-gray-300 text-sm py-1.5">
                                            <input type="hidden" :name="`rincian[${idx}][jumlah]`" :value="row.jumlah" :disabled="metode === 'hutang'">
                                            <button type="button" x-show="rincian.length > 1" @click="removeRincian(idx)"
                                                    class="text-gray-400 hover:text-red-600 px-1">&times;</button>
                                        </div>
                                        <input type="text" x-show="row.cara_bayar !== 'tunai'" x-cloak
                                               :name="`rincian[${idx}][no_referensi]`" x-model="row.no_referensi" :disabled="metode === 'hutang'"
                                               maxlength="50" placeholder="No. referensi QRIS/transfer"
                                               class="w-full rounded-md border-gray-300 text-xs py-1.5">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addRincian()" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                            + Tambah metode pembayaran
                        </button>

                        <p class="text-xs mt-2" :class="(rincianError || dpError) ? 'text-red-600 font-semibold' : (rincianKembalian > 0 ? 'text-green-600 font-semibold' : 'text-gray-400')">
                            <template x-if="metode === 'dp'">
                                <span>Jumlah DP: Rp <span x-text="rincianTotal.toLocaleString('id-ID')"></span>
                                    <span x-show="dpError" x-text="'— ' + dpError"></span>
                                </span>
                            </template>
                            <template x-if="metode !== 'dp'">
                                <span>
                                    Total rincian: <span x-text="rincianTotal.toLocaleString('id-ID')"></span>
                                    / <span x-text="totalBayar.toLocaleString('id-ID')"></span>
                                    <span x-show="rincianError" x-text="'— ' + rincianError"></span>
                                    <span x-show="rincianKembalian > 0" x-text="'— Kembalian: Rp ' + rincianKembalian.toLocaleString('id-ID')"></span>
                                </span>
                            </template>
                        </p>
                        <x-input-error :messages="$errors->get('rincian')" class="mt-1" />
                    </div>
                @endif

                <div>
                    <x-input-label for="catatan" value="Catatan (opsional)" />
                    <textarea id="catatan" name="catatan" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Proses Pembayaran
                </button>

                <button type="button" @click="invoiceModalOpen = true"
                        class="block w-full text-center text-sm text-gray-500 hover:underline">
                    Lihat / Cetak Surat Pesanan
                </button>
            </form>
        </div>
    </div>

    <div x-show="invoiceModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="invoiceModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg flex flex-col" style="width:95vw; max-width:1100px; height:94vh;">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">Surat Pesanan {{ $order->NoOrder }}</h3>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print()"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                        Cetak
                    </button>
                    <button type="button" @click="$refs.invoiceFrame.contentDocument.title = '{{ $order->NoOrder }}'; $refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print()"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-200">
                        PDF
                    </button>
                    <button type="button" @click="invoiceModalOpen = false"
                            class="inline-flex items-center px-2 py-1.5 text-gray-400 hover:text-gray-600 text-lg leading-none">
                        &times;
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-hidden">
                <iframe x-ref="invoiceFrame"
                        x-show="invoiceModalOpen"
                        :src="invoiceModalOpen ? '{{ route('invoice.show', ['type' => $type, 'id' => $order->id]) }}' : ''"
                        @load="if (autoPrintPending) { autoPrintPending = false; $refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print(); }"
                        class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>

    <div x-show="diskonModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data="{ diskonTipe: 'persen' }">
        <div @click="diskonModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
            <form method="POST" action="{{ route('kasir.diskon.request', ['type' => $type, 'id' => $order->id]) }}" class="p-5 space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-900">Ajukan Diskon — {{ $order->NoOrder }}</h3>
                <p class="text-xs text-gray-500">Diskon berlaku ke seluruh nota. Perlu disetujui Admin/Owner/Admin Kasir dulu sebelum bisa dipakai bayar.</p>

                <div>
                    <x-input-label value="Bentuk Diskon" />
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="diskon_tipe" value="persen" x-model="diskonTipe" class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Persen (%)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="diskon_tipe" value="nominal" x-model="diskonTipe" class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Nominal (Rp)</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('diskon_tipe')" class="mt-1" />
                </div>

                <div x-show="diskonTipe === 'persen'" x-cloak>
                    <x-input-label for="diskon_persen" value="Diskon (%)" />
                    <x-text-input id="diskon_persen" name="diskon_persen" type="number" step="0.01" min="0.01" max="100"
                                  class="mt-1 block w-full no-spinner" />
                    <x-input-error :messages="$errors->get('diskon_persen')" class="mt-1" />
                </div>

                <div x-show="diskonTipe === 'nominal'" x-cloak>
                    <x-input-label for="diskon_nominal" value="Diskon (Rp)" />
                    <x-text-input id="diskon_nominal" name="diskon_nominal" type="number" min="100" step="100" max="{{ (int) $order->total }}"
                                  class="mt-1 block w-full no-spinner" />
                    <p class="text-xs text-gray-400 mt-1">Maksimal Rp {{ number_format($order->total, 0, ',', '.') }} (total nota).</p>
                    <x-input-error :messages="$errors->get('diskon_nominal')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="diskon_alasan" value="Alasan" />
                    <textarea id="diskon_alasan" name="diskon_alasan" rows="2" required maxlength="255"
                              placeholder="misal: customer langganan, komplain kualitas, dll"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    <x-input-error :messages="$errors->get('diskon_alasan')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="diskonModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Ajukan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="cancelModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="cancelModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
            <form method="POST" action="{{ route('order-' . $type . '.request-cancel', $order->id) }}" class="p-5 space-y-4">
                @csrf
                <h3 class="font-semibold text-gray-900">Ajukan pembatalan — {{ $order->NoOrder }}</h3>
                <p class="text-xs text-gray-500">Order akan ditandai menunggu persetujuan. Perlu disetujui Admin/Admin Kasir dari Antrian Desain sebelum benar-benar dibatalkan.</p>

                <div>
                    <x-input-label for="cancel_reason" value="Alasan pembatalan" />
                    <textarea id="cancel_reason" name="cancel_reason" rows="3" required maxlength="255"
                              placeholder="misal: customer minta batal, salah spesifikasi, dll"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    <x-input-error :messages="$errors->get('cancel_reason')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="cancelModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Ajukan pembatalan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="batalOrderModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="batalOrderModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
            <form method="POST" action="{{ route('order-rework.store', [$type, $order->id]) }}" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="action" value="batal">
                <h3 class="font-semibold text-gray-900">Batalkan Order — {{ $order->NoOrder }}</h3>
                <p class="text-xs text-gray-500">Order akan dibatalkan total dan uang yang sudah dibayar dikembalikan ke customer. Perlu disetujui dulu di menu Approval.</p>

                <div>
                    <x-input-label for="batal_order_reason" value="Alasan" />
                    <textarea id="batal_order_reason" name="reason" rows="3" required maxlength="255"
                              placeholder="Jelaskan alasan pembatalan..."
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="batalOrderModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Ajukan pembatalan</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</x-app-layout>
