<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Customer Service</h2>
            <p class="mt-1 text-sm text-slate-500">Info transfer untuk Kasir.</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    @can('customer-service.manage')
        <section class="mb-6 overflow-hidden rounded border border-slate-300 bg-white shadow-sm"
                 x-data="{ items: @js(old('items', [['order_type' => 'OD', 'width' => '', 'height' => '', 'quantity' => 1, 'material' => '', 'printer' => '', 'finishing' => '']])), addItem() { this.items.push({ order_type: 'OD', width: '', height: '', quantity: 1, material: '', printer: '', finishing: '' }) }, removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1) } }">
            <div class="flex flex-col gap-2 border-b border-slate-700 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between" style="background:#17233c">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-300">Lembar Kerja CS</p>
                    <h3 class="mt-1 text-lg font-semibold">Form Permintaan Produksi</h3>
                </div>
                <p class="text-xs text-slate-300">OD / ID / AW </p>
            </div>

            <form method="POST" action="{{ route('customer-service.job-sheet.store') }}">
                @csrf
                <div class="grid gap-4 border-b border-slate-200 p-5 md:grid-cols-2 xl:grid-cols-4">
                    <div class="relative text-xs font-semibold uppercase tracking-wide text-slate-500 xl:col-span-2"
                         x-data="{
                            query: @js($selectedCustomer ? $selectedCustomer->NmCust.' ('.$selectedCustomer->KdCust.')' : ''),
                            selectedCode: @js(old('customer_code', '')),
                            results: [], open: false, timer: null,
                            search() {
                                this.selectedCode = '';
                                clearTimeout(this.timer);
                                this.timer = setTimeout(async () => {
                                    const response = await fetch(`/customers-search?q=${encodeURIComponent(this.query)}`);
                                    this.results = await response.json();
                                    this.open = true;
                                }, 250);
                            },
                            select(customer) {
                                this.selectedCode = customer.KdCust;
                                this.query = `${customer.NmCust} (${customer.KdCust})`;
                                this.open = false;
                            }
                         }" @click.outside="open = false">
                        <label for="cs-customer-search">Nama Customer</label>
                        <input id="cs-customer-search" type="text" x-model="query" @input="search()" @focus="search()"
                               autocomplete="off" placeholder="Cari nama atau kode customer..." required
                               class="mt-1.5 w-full rounded border-slate-300 text-sm font-normal normal-case tracking-normal text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        <input type="hidden" name="customer_code" :value="selectedCode">
                        <div x-show="open && results.length" x-cloak
                             class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded border border-slate-200 bg-white py-1 text-sm font-normal normal-case tracking-normal shadow-xl">
                            <template x-for="customer in results" :key="customer.KdCust">
                                <button type="button" @click="select(customer)" class="block w-full px-3 py-2 text-left text-slate-700 hover:bg-blue-50">
                                    <span class="font-medium" x-text="customer.NmCust"></span>
                                    <span class="ml-1 text-xs text-slate-400" x-text="`(${customer.KdCust})`"></span>
                                    <span x-show="customer.Telp" class="mt-0.5 block text-xs text-slate-400" x-text="customer.Telp"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="query && !selectedCode" class="mt-1 text-[11px] font-normal normal-case tracking-normal text-amber-600">Pilih customer dari hasil pencarian.</p>
                        <x-input-error :messages="$errors->get('customer_code')" class="mt-1 normal-case tracking-normal" />
                    </div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        PC <span class="font-normal normal-case tracking-normal text-slate-400">(opsional)</span>
                        <input name="pc" value="{{ old('pc') }}" maxlength="100"
                               class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Folder / File <span class="font-normal normal-case tracking-normal text-slate-400">(opsional)</span>
                        <input name="folder_file" value="{{ old('folder_file') }}" maxlength="255"
                               class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Tanggal Masuk
                        <input type="date" name="received_at" value="{{ old('received_at', now()->format('Y-m-d')) }}" required
                               class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Deadline <span class="font-normal normal-case tracking-normal text-slate-400">(opsional)</span>
                        <input type="date" name="deadline" value="{{ old('deadline') }}"
                               class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Pembuat Order
                        <input value="{{ auth()->user()->name }}" readonly
                               class="mt-1.5 w-full rounded border-slate-200 bg-slate-100 text-sm font-medium normal-case tracking-normal text-slate-600">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        OPF <span class="font-normal normal-case tracking-normal text-slate-400">(opsional)</span>
                        <input name="opf" value="{{ old('opf') }}" maxlength="100"
                               class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 md:col-span-2 xl:col-span-4">
                        Keterangan
                        <textarea name="notes" rows="2" maxlength="1000"
                                  class="mt-1.5 w-full rounded border-slate-300 text-sm normal-case tracking-normal focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </label>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-sm">
                        <thead class="border-b border-slate-200 bg-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center">No.</th>
                                <th class="w-28 px-3 py-2">Jenis Order</th>
                                <th class="w-28 px-3 py-2">Lebar (cm)</th>
                                <th class="w-28 px-3 py-2">Tinggi (cm)</th>
                                <th class="w-28 px-3 py-2">Jumlah Cetak</th>
                                <th class="px-3 py-2">Material / Bahan</th>
                                <th class="px-3 py-2">Printer</th>
                                <th class="px-3 py-2">Finishing</th>
                                <th class="w-14 px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-3 py-2 text-center text-slate-400" x-text="index + 1"></td>
                                    <td class="px-3 py-2"><select :name="`items[${index}][order_type]`" x-model="item.order_type" class="w-full rounded border-slate-300 text-sm"><option>OD</option><option>ID</option><option>AW</option></select></td>
                                    <td class="px-3 py-2"><input type="number" min="0.01" step="0.01" required :name="`items[${index}][width]`" x-model="item.width" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2"><input type="number" min="0.01" step="0.01" required :name="`items[${index}][height]`" x-model="item.height" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2"><input type="number" min="1" required :name="`items[${index}][quantity]`" x-model="item.quantity" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2"><input required maxlength="150" :name="`items[${index}][material]`" x-model="item.material" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2"><input maxlength="150" :name="`items[${index}][printer]`" x-model="item.printer" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2"><input maxlength="150" :name="`items[${index}][finishing]`" x-model="item.finishing" class="w-full rounded border-slate-300 text-sm"></td>
                                    <td class="px-3 py-2 text-center"><button type="button" @click="removeItem(index)" :disabled="items.length === 1" class="text-xs font-semibold text-red-600 disabled:cursor-not-allowed disabled:text-slate-300">Hapus</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <button type="button" @click="addItem()" class="rounded border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Tambah Baris</button>
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Simpan Lembar Kerja</button>
                </div>
            </form>
        </section>
    @endcan

</x-app-layout>
