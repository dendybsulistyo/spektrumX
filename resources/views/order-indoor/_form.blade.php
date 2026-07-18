@php
    $order = $order ?? null;
    $initialItems = isset($items) && $items->isNotEmpty()
        ? $items->map(fn ($i) => ['KdProd' => $i->KdProd, 'Judul' => $i->Judul, 'Panjang' => $i->Panjang, 'Lebar' => $i->Lebar, 'Qty' => $i->Qty])->values()
        : collect([['KdProd' => '', 'Judul' => '', 'Panjang' => '', 'Lebar' => '', 'Qty' => 1]]);
    $selectedCustomerLabel = $selectedCustomer ? "{$selectedCustomer->NmCust} ({$selectedCustomer->KdCust})" : '';
    $submitLabel = $order ? 'Simpan Perubahan' : 'Simpan Order';
@endphp

<div x-data="{
        items: {{ old('items') ? json_encode(old('items')) : $initialItems->toJson() }},
        produkMap: @js($produkList->mapWithKeys(fn ($p) => [$p->KdProd => [
            'HargaStd' => $p->HargaStd,
            'HargaMin' => $p->HargaMin,
            'isPjLb' => $p->isPjLb,
            'Satuan' => $p->Satuan,
        ]])),
        needsDimension(kdProd) {
            const p = this.produkMap[kdProd];
            return p ? [2, 3].includes(p.isPjLb) : false;
        },
        dimensionUnit(kdProd) {
            const p = this.produkMap[kdProd];
            return p && p.Satuan === 'sqm' ? '(m)' : '(cm)';
        },
        onProdukChange(item) {
            if (!this.needsDimension(item.KdProd)) {
                item.Panjang = 0;
                item.Lebar = 0;
            }
        },
        lineTotal(item) {
            const p = this.produkMap[item.KdProd];
            if (!p) return 0;
            const qty = Number(item.Qty) || 0;
            const raw = p.isPjLb === 2
                ? p.HargaStd * (Number(item.Panjang) || 0) * (Number(item.Lebar) || 0) * qty
                : p.HargaStd * qty;
            return Math.max(raw, p.HargaMin || 0);
        },
        get grandTotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },
    }">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div>
            <x-input-label for="TglOrder" value="Tanggal Order" />
            <x-text-input id="TglOrder" name="TglOrder" type="date" class="mt-1 block w-full"
                value="{{ old('TglOrder', $order?->TglOrder ?? now()->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('TglOrder')" class="mt-1" />
        </div>

        <div class="relative"
             x-data="{
                query: @js($selectedCustomerLabel),
                selectedKdCust: @js(old('KdCust', $selectedCustomer?->KdCust ?? '')),
                results: [],
                open: false,
                searchTimer: null,
                search() {
                    this.selectedKdCust = '';
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(async () => {
                        const res = await fetch(`/customers-search?q=${encodeURIComponent(this.query)}`);
                        this.results = await res.json();
                        this.open = true;
                    }, 250);
                },
                select(c) {
                    this.selectedKdCust = c.KdCust;
                    this.query = `${c.NmCust} (${c.KdCust})`;
                    this.open = false;
                },
             }"
             @click.outside="open = false">
            <x-input-label for="KdCustSearch" value="Customer" />
            <input type="text" id="KdCustSearch" x-model="query" @input="search()" @focus="search()"
                   autocomplete="off" placeholder="Cari nama atau kode customer..."
                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="KdCust" :value="selectedKdCust">

            <div x-show="open && results.length > 0" x-cloak
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                <template x-for="c in results" :key="c.KdCust">
                    <button type="button" @click="select(c)"
                            class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100">
                        <span x-text="c.NmCust"></span> <span class="text-gray-400" x-text="'(' + c.KdCust + ')'"></span>
                    </button>
                </template>
            </div>
            <p class="text-xs text-gray-400 mt-1" x-show="open && query.length > 0 && results.length === 0">
                Customer tidak ditemukan.
            </p>

            <div x-show="!selectedKdCust">
                <x-input-error :messages="$errors->get('KdCust')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="border-t pt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Item Order</h3>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start mb-1 p-3 bg-gray-50 rounded-md">
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Produk</label>
                    <select :name="`items[${index}][KdProd]`" x-model="item.KdProd" @change="onProdukChange(item)" required
                            class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach ($produkList as $p)
                            <option value="{{ $p->KdProd }}">{{ $p->NmProd }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Judul</label>
                    <input type="text" :name="`items[${index}][Judul]`" x-model="item.Judul" maxlength="30" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Panjang <span x-text="dimensionUnit(item.KdProd)"></span></label>
                    <input type="number" step="0.01" :name="`items[${index}][Panjang]`" x-model="item.Panjang"
                           :readonly="!needsDimension(item.KdProd)" :required="needsDimension(item.KdProd)"
                           :class="!needsDimension(item.KdProd) && 'bg-gray-100 text-gray-400'"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Lebar <span x-text="dimensionUnit(item.KdProd)"></span></label>
                    <input type="number" step="0.01" :name="`items[${index}][Lebar]`" x-model="item.Lebar"
                           :readonly="!needsDimension(item.KdProd)" :required="needsDimension(item.KdProd)"
                           :class="!needsDimension(item.KdProd) && 'bg-gray-100 text-gray-400'"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs text-gray-500 mb-1">Qty</label>
                    <input type="number" :name="`items[${index}][Qty]`" x-model="item.Qty" min="1" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="col-span-2 sm:col-span-1 flex sm:items-end h-full pt-1 sm:pt-5">
                    <button type="button" @click="items.length > 1 && items.splice(index, 1)"
                            class="inline-flex items-center text-red-600 hover:text-red-800" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-3 px-3" x-show="item.KdProd">
                Estimasi subtotal: <span x-text="'Rp ' + lineTotal(item).toLocaleString('id-ID')"></span>
            </p>
        </template>

        <x-input-error :messages="$errors->get('items')" class="mt-1" />

        <div class="border-t pt-3 mt-2 flex justify-end">
            <p class="text-sm font-semibold text-gray-700">
                Estimasi Total: <span x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></span>
            </p>
        </div>
    </div>

    <div class="pt-4 flex gap-3 border-t mt-4">
        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('order-indoor.index') }}" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
            Batal
        </a>
        <button type="button" @click="items.push({ KdProd: '', Judul: '', Panjang: '', Lebar: '', Qty: 1 })"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Tambah Item
        </button>
    </div>
</div>
