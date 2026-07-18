@php
    $order = $order ?? null;
    $initialItems = isset($items) && $items->isNotEmpty()
        ? $items->map(fn ($i) => [
            'NmFile' => $i->NmFile, 'Panjang' => $i->Panjang, 'Lebar' => $i->Lebar,
            'Qty' => $i->Qty, 'KdCtk' => $i->KdCtk, 'KdBrgs' => $i->KdBrgs, 'Fins' => $i->Fins,
        ])->values()
        : collect([['NmFile' => '', 'Panjang' => '', 'Lebar' => '', 'Qty' => 1, 'KdCtk' => '', 'KdBrgs' => '', 'Fins' => '']]);
    $selectedCustomerLabel = $selectedCustomer ? "{$selectedCustomer->NmCust} ({$selectedCustomer->KdCust})" : '';
@endphp

<div x-data="{
        items: {{ old('items') ? json_encode(old('items')) : $initialItems->toJson() }},
        addItem() {
            this.items.push({ NmFile: '', Panjang: '', Lebar: '', Qty: 1, KdCtk: '', KdBrgs: '', Fins: '' });
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('[data-item-search]');
                inputs[inputs.length - 1]?.focus();
            });
        },
        onDeleteTab(event, index) {
            if (!event.shiftKey && index === this.items.length - 1) {
                event.preventDefault();
                this.addItem();
            }
        },
    }">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div>
            <x-input-label for="TglOrder" value="Tanggal Order" />
            <x-text-input id="TglOrder" name="TglOrder" type="date" class="mt-1 block w-full"
                value="{{ old('TglOrder', $order?->TglOrder?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('TglOrder')" class="mt-1" />
        </div>

        <div class="relative"
             x-data="{
                query: @js($selectedCustomerLabel),
                selectedKdCust: @js(old('KdCust', $selectedCustomer?->KdCust ?? '')),
                results: [],
                open: false,
                activeIndex: -1,
                searchTimer: null,
                quickAddOpen: false,
                quickAddForm: { NmCust: '', Telp: '', Alamat: '', Kota: '' },
                quickAddSaving: false,
                quickAddErrors: {},
                get displayResults() {
                    const list = this.results.slice();
                    if (this.query.trim().length > 0) {
                        list.push({ __new: true, NmCust: this.query.trim() });
                    }
                    return list;
                },
                search() {
                    this.selectedKdCust = '';
                    this.activeIndex = -1;
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(async () => {
                        const res = await fetch(`/customers-search?q=${encodeURIComponent(this.query)}`);
                        this.results = await res.json();
                        this.open = true;
                    }, 250);
                },
                select(c) {
                    if (c.__new) {
                        this.openQuickAdd(c.NmCust);
                        return;
                    }
                    this.selectedKdCust = c.KdCust;
                    this.query = `${c.NmCust} (${c.KdCust})`;
                    this.open = false;
                    this.activeIndex = -1;
                },
                move(delta) {
                    if (!this.open || this.displayResults.length === 0) return;
                    this.activeIndex = (this.activeIndex + delta + this.displayResults.length) % this.displayResults.length;
                    this.$nextTick(() => {
                        const list = document.getElementById('cust-search-list');
                        const active = document.getElementById('cust-search-item-' + this.activeIndex);
                        if (!list || !active) return;
                        const listRect = list.getBoundingClientRect();
                        const itemRect = active.getBoundingClientRect();
                        if (itemRect.top < listRect.top) {
                            list.scrollTop -= (listRect.top - itemRect.top);
                        } else if (itemRect.bottom > listRect.bottom) {
                            list.scrollTop += (itemRect.bottom - listRect.bottom);
                        }
                    });
                },
                chooseActive() {
                    if (this.activeIndex >= 0 && this.displayResults[this.activeIndex]) {
                        this.select(this.displayResults[this.activeIndex]);
                    }
                },
                openQuickAdd(name) {
                    this.quickAddForm = { NmCust: name, Telp: '', Alamat: '', Kota: '' };
                    this.quickAddErrors = {};
                    this.quickAddOpen = true;
                    this.open = false;
                },
                async submitQuickAdd() {
                    this.quickAddSaving = true;
                    this.quickAddErrors = {};
                    try {
                        const res = await fetch('/customers-quick-create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(this.quickAddForm),
                        });
                        if (res.status === 422) {
                            const data = await res.json();
                            this.quickAddErrors = data.errors || {};
                            this.quickAddSaving = false;
                            return;
                        }
                        if (!res.ok) throw new Error('Gagal menyimpan');
                        const customer = await res.json();
                        this.quickAddOpen = false;
                        this.select(customer);
                    } catch (e) {
                        alert('Gagal menambah customer baru.');
                    } finally {
                        this.quickAddSaving = false;
                    }
                },
             }"
             @click.outside="open = false">
            <x-input-label for="KdCustSearch" value="Customer" />
            <input type="text" id="KdCustSearch" x-model="query" @input="search()" @focus="search()"
                   @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                   @keydown.enter.prevent="chooseActive()" @keydown.escape="open = false"
                   autocomplete="off" placeholder="Cari nama atau kode customer..."
                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="KdCust" :value="selectedKdCust">

            <div x-show="open && displayResults.length > 0" x-cloak id="cust-search-list"
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                <template x-for="(c, i) in displayResults" :key="c.__new ? '__new' : c.KdCust">
                    <button type="button" @click="select(c)" @mouseenter="activeIndex = i" :id="'cust-search-item-' + i"
                            :class="i === activeIndex ? 'bg-gray-100' : ''"
                            class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100">
                        <template x-if="!c.__new">
                            <div>
                                <span x-text="c.NmCust"></span> <span class="text-gray-400" x-text="'(' + c.KdCust + ')'"></span>
                                <template x-if="c.Telp">
                                    <span class="block text-xs text-gray-400" x-text="c.Telp"></span>
                                </template>
                            </div>
                        </template>
                        <template x-if="c.__new">
                            <div class="text-green-700 font-medium">
                                + Tambah customer baru: <span x-text="c.NmCust"></span>
                            </div>
                        </template>
                    </button>
                </template>
            </div>

            <div x-show="!selectedKdCust">
                <x-input-error :messages="$errors->get('KdCust')" class="mt-1" />
            </div>

            {{-- Modal: tambah customer baru cepat --}}
            <div x-show="quickAddOpen" x-cloak @keydown.escape.window="quickAddOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="quickAddOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

                <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md" @click.stop>
                    <div class="border-b border-gray-200 flex items-center justify-between" style="padding: 16px 24px;">
                        <h3 class="font-semibold text-gray-900">Tambah Customer Baru</h3>
                        <button type="button" @click="quickAddOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitQuickAdd()" style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
                        <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md leading-relaxed" style="padding: 10px 12px;">
                            Kode customer dibuat otomatis. Detail lain bisa dilengkapi di menu Customer.
                        </p>
                        <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Nama</label>
                            <input type="text" x-model="quickAddForm.NmCust" required autofocus
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   style="padding: 10px 12px;">
                            <p class="text-xs text-red-600" style="margin-top: 6px;" x-show="quickAddErrors.NmCust" x-text="quickAddErrors.NmCust?.[0]"></p>
                        </div>
                        <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Telepon</label>
                            <input type="text" x-model="quickAddForm.Telp" required
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   style="padding: 10px 12px;">
                            <p class="text-xs text-red-600" style="margin-top: 6px;" x-show="quickAddErrors.Telp" x-text="quickAddErrors.Telp?.[0]"></p>
                        </div>
                        <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Alamat <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="text" x-model="quickAddForm.Alamat"
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   style="padding: 10px 12px;">
                        </div>
                        <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Kota <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="text" x-model="quickAddForm.Kota"
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   style="padding: 10px 12px;">
                        </div>

                        <div class="flex gap-3 border-t border-gray-200" style="padding:10px 10px" >
                            <button type="submit" :disabled="quickAddSaving"
                                    class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50">
                                <span x-show="!quickAddSaving">Simpan &amp; Pilih</span>
                                <span x-show="quickAddSaving">Menyimpan...</span>
                            </button>
                            <button type="button" @click="quickAddOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="KdOpr" value="Operator" />
            <select id="KdOpr" name="KdOpr" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Pilih Operator --</option>
                @foreach ($operators as $o)
                    <option value="{{ $o->KdOpr }}" @selected(old('KdOpr', $order?->KdOpr) === $o->KdOpr)>
                        {{ $o->NmOpr }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('KdOpr')" class="mt-1" />
        </div>
    </div>

    <div class="border-t pt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Item Order</h3>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start mb-3 p-3 bg-gray-50 rounded-md">
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Nama File / Desain</label>
                    <input type="text" :name="`items[${index}][NmFile]`" x-model="item.NmFile" required
                           data-item-search
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Panjang (cm)</label>
                    <input type="number" step="0.01" :name="`items[${index}][Panjang]`" x-model="item.Panjang" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Lebar (cm)</label>
                    <input type="number" step="0.01" :name="`items[${index}][Lebar]`" x-model="item.Lebar" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs text-gray-500 mb-1">Qty</label>
                    <input type="number" :name="`items[${index}][Qty]`" x-model="item.Qty" min="1" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Kode Cetak</label>
                    <select :name="`items[${index}][KdCtk]`" x-model="item.KdCtk" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">-- Opsional --</option>
                        @foreach ($hargaCetakList as $h)
                            <option value="{{ $h->KdCtk }}">{{ $h->KdCtk }} (Rp {{ number_format($h->HargaStd, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Bahan</label>
                    <select :name="`items[${index}][KdBrgs]`" x-model="item.KdBrgs" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">-- Opsional --</option>
                        @foreach ($bahanList as $b)
                            <option value="{{ $b->KdBrgs }}">{{ $b->NmBrgs }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-11">
                    <label class="block text-xs text-gray-500 mb-1">Catatan Finishing</label>
                    <input type="text" :name="`items[${index}][Fins]`" x-model="item.Fins" placeholder="misal: SB, laminasi doff, dll"
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="col-span-2 sm:col-span-1 flex justify-end sm:justify-start sm:items-end h-full pt-1 sm:pt-5">
                    <button type="button" @click="items.length > 1 && items.splice(index, 1)"
                            @keydown.tab="onDeleteTab($event, index)"
                            class="inline-flex items-center text-red-600 hover:text-red-800" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <x-input-error :messages="$errors->get('items')" class="mt-1" />
    </div>

    <div class="pt-4 flex gap-3 border-t mt-4">
        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
            {{ $order ? 'Simpan Perubahan' : 'Simpan Order' }}
        </button>
        <a href="{{ route('order-outdoor.index') }}" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
            Batal
        </a>
        <button type="button" @click="addItem()"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Tambah Item
        </button>
    </div>
</div>
