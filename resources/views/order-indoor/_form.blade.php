@php
    $order = $order ?? null;
    $initialItems = isset($items) && $items->isNotEmpty()
        ? $items->map(fn ($i) => [
            'KdProd' => $i->KdProd, 'Judul' => $i->Judul, 'Panjang' => $i->Panjang, 'Lebar' => $i->Lebar, 'Qty' => $i->Qty,
            'PisauTurun' => $i->PisauTurun, 'JumlahKertas' => $i->JumlahKertas, 'TebalKertas' => $i->TebalKertas,
        ])->values()
        : collect([['KdProd' => '', 'Judul' => '', 'Panjang' => '', 'Lebar' => '', 'Qty' => 1, 'PisauTurun' => null, 'JumlahKertas' => null, 'TebalKertas' => null]]);
    $selectedCustomerLabel = $selectedCustomer ? "{$selectedCustomer->NmCust} ({$selectedCustomer->KdCust})" : '';
    $submitLabel = $order ? 'Simpan Perubahan' : 'Simpan Order';
@endphp

<div x-data="{
        items: {{ old('items') ? json_encode(old('items')) : $initialItems->toJson() }},
        produkMap: @js($produkList->mapWithKeys(fn ($p) => [$p->KdProd => [
            'NmProd' => $p->NmProd,
            'HargaStd' => $p->HargaStd,
            'HargaMin' => $p->HargaMin,
            'isPjLb' => $p->isPjLb,
            'Satuan' => $p->Satuan,
        ]])),
        produkOptions: @js($produkList->map(fn ($p) => ['KdProd' => $p->KdProd, 'NmProd' => $p->NmProd, 'KdDivs' => $p->KdDivs])),
        kategoriList: @js($kategoriList->map(fn ($k) => ['KdDivs' => $k->KdDivs, 'NmDivs' => $k->NmDivs])),
        nilaiX: {{ (float) $nilaiX }},
        potongModalIndex: null,
        needsDimension(kdProd) {
            const p = this.produkMap[kdProd];
            return p ? [2, 3].includes(p.isPjLb) : false;
        },
        isJasaPotong(kdProd) {
            const p = this.produkMap[kdProd];
            return p ? p.isPjLb === 4 : false;
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
            if (this.isJasaPotong(item.KdProd)) {
                this.potongModalIndex = this.items.indexOf(item);
            } else {
                item.PisauTurun = null;
                item.JumlahKertas = null;
                item.TebalKertas = null;
            }
        },
        potongTotal(item) {
            const p = Number(item.PisauTurun) || 0;
            const q = Number(item.JumlahKertas) || 0;
            const tb = Number(item.TebalKertas) || 0;
            return ((p * q * tb) / 10) + this.nilaiX;
        },
        syncPotongQty(index) {
            const item = this.items[index];
            item.Qty = Math.round(this.potongTotal(item));
        },
        lineTotal(item) {
            const p = this.produkMap[item.KdProd];
            if (!p) return 0;
            const qty = Number(item.Qty) || 0;
            let raw;
            if (p.isPjLb === 4) {
                raw = this.potongTotal(item);
            } else if (p.isPjLb === 2) {
                raw = p.HargaStd * (Number(item.Panjang) || 0) * (Number(item.Lebar) || 0) * qty;
            } else {
                raw = p.HargaStd * qty;
            }
            return Math.max(raw, p.HargaMin || 0);
        },
        get grandTotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },
        addItem() {
            this.items.push({ KdProd: '', Judul: '', Panjang: '', Lebar: '', Qty: 1, PisauTurun: null, JumlahKertas: null, TebalKertas: null });
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('[data-produk-search]');
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

    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div style="width: 100%; max-width: 200px;">
            <x-input-label for="TglOrder" value="Tanggal Order" />
            <x-text-input id="TglOrder" name="TglOrder" type="date" class="mt-1 block w-full"
                value="{{ old('TglOrder', $order?->TglOrder ?? now()->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('TglOrder')" class="mt-1" />
        </div>

        <div class="relative flex-1"
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

                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
                        <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md leading-relaxed" style="padding: 10px 12px;">
                            Kode customer dibuat otomatis. Detail lain bisa dilengkapi di menu Customer.
                        </p>
                        <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Nama</label>
                            <input type="text" x-model="quickAddForm.NmCust" autofocus
                                   class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   style="padding: 10px 12px;">
                            <p class="text-xs text-red-600" style="margin-top: 6px;" x-show="quickAddErrors.NmCust" x-text="quickAddErrors.NmCust?.[0]"></p>
                        </div>
                       <div style="padding: 10px 10px;">
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 8px;">Telepon</label>
                            <input type="text" x-model="quickAddForm.Telp"
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
                            <button type="button" @click="submitQuickAdd()" :disabled="quickAddSaving"
                                    class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50">
                                <span x-show="!quickAddSaving">Simpan &amp; Pilih</span>
                                <span x-show="quickAddSaving">Menyimpan...</span>
                            </button>
                            <button type="button" @click="quickAddOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t pt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Item Order</h3>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start mb-1 p-3 bg-gray-50 rounded-md">
                <div class="col-span-2 sm:col-span-3 relative"
                     x-data="{
                        query: produkMap[item.KdProd] ? `${produkMap[item.KdProd].NmProd} (${item.KdProd})` : '',
                        open: false,
                        activeIndex: -1,
                        kategoriModalOpen: false,
                        selectedKategori: null,
                        kategoriFocusZone: 'kategori',
                        activeKategoriIndex: -1,
                        activeProdukIndex: -1,
                        get results() {
                            const q = this.query.trim().toLowerCase();
                            if (q === '') return produkOptions.slice(0, 30);
                            return produkOptions.filter(p =>
                                p.NmProd.toLowerCase().includes(q) || p.KdProd.toLowerCase().includes(q)
                            ).slice(0, 30);
                        },
                        get produkInKategori() {
                            if (!this.selectedKategori) return [];
                            return produkOptions.filter(p => p.KdDivs === this.selectedKategori);
                        },
                        openKategoriModal() {
                            this.selectedKategori = null;
                            this.kategoriModalOpen = true;
                            this.open = false;
                            this.kategoriFocusZone = 'kategori';
                            this.activeKategoriIndex = -1;
                            this.activeProdukIndex = -1;
                            this.$nextTick(() => this.$refs.kategoriPanel?.focus());
                        },
                        selectFromKategori(p) {
                            this.select(p);
                            this.kategoriModalOpen = false;
                        },
                        pickKategori(i) {
                            this.activeKategoriIndex = i;
                            this.selectedKategori = kategoriList[i].KdDivs;
                            this.activeProdukIndex = -1;
                        },
                        moveKategori(delta) {
                            if (kategoriList.length === 0) return;
                            const next = this.activeKategoriIndex < 0 ? 0 : Math.max(0, Math.min(kategoriList.length - 1, this.activeKategoriIndex + delta));
                            this.pickKategori(next);
                            this.$nextTick(() => {
                                document.getElementById('kat-item-' + index + '-' + next)
                                    ?.scrollIntoView({ block: 'nearest' });
                            });
                        },
                        moveProduk(delta) {
                            if (this.produkInKategori.length === 0) return;
                            this.activeProdukIndex = this.activeProdukIndex < 0 ? 0 : Math.max(0, Math.min(this.produkInKategori.length - 1, this.activeProdukIndex + delta));
                            this.$nextTick(() => {
                                document.getElementById('prod-kat-item-' + index + '-' + this.activeProdukIndex)
                                    ?.scrollIntoView({ block: 'nearest' });
                            });
                        },
                        focusProdukPane() {
                            if (this.produkInKategori.length === 0) return;
                            this.kategoriFocusZone = 'produk';
                            this.activeProdukIndex = 0;
                        },
                        focusKategoriPane() {
                            this.kategoriFocusZone = 'kategori';
                        },
                        chooseActiveProduk() {
                            if (this.activeProdukIndex >= 0 && this.produkInKategori[this.activeProdukIndex]) {
                                this.selectFromKategori(this.produkInKategori[this.activeProdukIndex]);
                            }
                        },
                        select(p) {
                            item.KdProd = p.KdProd;
                            this.query = `${p.NmProd} (${p.KdProd})`;
                            this.open = false;
                            this.activeIndex = -1;
                            onProdukChange(item);
                        },
                        move(delta) {
                            if (!this.open || this.results.length === 0) return;
                            this.activeIndex = (this.activeIndex + delta + this.results.length) % this.results.length;
                            this.$nextTick(() => {
                                const list = document.getElementById('prod-search-list-' + index);
                                const active = document.getElementById('prod-search-item-' + index + '-' + this.activeIndex);
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
                            if (this.activeIndex >= 0 && this.results[this.activeIndex]) {
                                this.select(this.results[this.activeIndex]);
                            }
                        },
                     }"
                     @click.outside="open = false">
                    <label class="block text-xs text-gray-500 mb-1">Produk</label>
                    <div class="flex gap-1">
                        <input type="text" x-model="query" @focus="open = true" @input="open = true; activeIndex = -1"
                               @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                               @keydown.enter.prevent="chooseActive()" @keydown.escape="open = false"
                               autocomplete="off" placeholder="Cari produk..." data-produk-search
                               class="w-full rounded-md border-gray-300 text-sm">
                        <button type="button" @click="openKategoriModal()" title="Pilih dari Kategori"
                                class="shrink-0 px-2 rounded-md border border-gray-300 text-gray-500 hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008zM6 6.75h14.25M6 12h14.25M6 17.25h14.25" />
                            </svg>
                        </button>
                    </div>
                    <input type="hidden" :name="`items[${index}][KdProd]`" :value="item.KdProd" required>

                    <div x-show="open && results.length > 0" x-cloak :id="'prod-search-list-' + index"
                         class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="(p, i) in results" :key="p.KdProd">
                            <button type="button" @click="select(p)" @mouseenter="activeIndex = i" :id="'prod-search-item-' + index + '-' + i"
                                    :class="i === activeIndex ? 'bg-gray-100' : ''"
                                    class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100">
                                <span x-text="p.NmProd"></span> <span class="text-gray-400" x-text="'(' + p.KdProd + ')'"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-1" x-show="open && query.length > 0 && results.length === 0">
                        Produk tidak ditemukan.
                    </p>

                    {{-- Modal: pilih produk lewat kategori --}}
                    <div x-show="kategoriModalOpen" x-cloak @keydown.escape.window="kategoriModalOpen = false"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div @click="kategoriModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

                        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-2xl flex flex-col" style="max-height: 80vh; outline: none;" @click.stop
                             x-ref="kategoriPanel" tabindex="-1"
                             @keydown.down.prevent="kategoriFocusZone === 'kategori' ? moveKategori(1) : moveProduk(1)"
                             @keydown.up.prevent="kategoriFocusZone === 'kategori' ? moveKategori(-1) : moveProduk(-1)"
                             @keydown.right.prevent="kategoriFocusZone === 'kategori' && focusProdukPane()"
                             @keydown.left.prevent="kategoriFocusZone === 'produk' && focusKategoriPane()"
                             @keydown.enter.prevent="kategoriFocusZone === 'produk' && chooseActiveProduk()">
                            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                                <h3 class="font-semibold text-gray-900">Pilih Produk dari Kategori</h3>
                                <button type="button" @click="kategoriModalOpen = false" class="text-gray-400 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <p class="px-5 pt-2 text-xs text-gray-400 shrink-0">
                                Pakai ↑↓ untuk pilih kategori, → untuk lihat produk, ← untuk kembali, Enter untuk pilih produk.
                            </p>

                            <div class="grid grid-cols-2 flex-1" style="min-height: 0;">
                                <div class="border-r border-gray-200 overflow-y-auto">
                                    <template x-for="(k, i) in kategoriList" :key="k.KdDivs">
                                        <button type="button" @click="pickKategori(i); kategoriFocusZone = 'kategori'"
                                                :id="'kat-item-' + index + '-' + i"
                                                :class="activeKategoriIndex === i ? 'bg-indigo-50 text-indigo-700 font-medium' : (selectedKategori === k.KdDivs ? 'bg-gray-50 text-gray-700' : 'text-gray-700 hover:bg-gray-50')"
                                                class="block w-full text-left px-3 py-2 text-sm border-b border-gray-100">
                                            <span x-text="k.NmDivs"></span>
                                        </button>
                                    </template>
                                </div>
                                <div class="overflow-y-auto">
                                    <template x-if="!selectedKategori">
                                        <p class="text-xs text-gray-400 p-3">← Pilih kategori dulu.</p>
                                    </template>
                                    <template x-if="selectedKategori && produkInKategori.length === 0">
                                        <p class="text-xs text-gray-400 p-3">Tidak ada produk di kategori ini.</p>
                                    </template>
                                    <template x-for="(p, i) in produkInKategori" :key="p.KdProd">
                                        <button type="button" @click="selectFromKategori(p)" @mouseenter="activeProdukIndex = i"
                                                :id="'prod-kat-item-' + index + '-' + i"
                                                :class="kategoriFocusZone === 'produk' && activeProdukIndex === i ? 'bg-indigo-50 text-indigo-700 font-medium' : 'hover:bg-gray-50'"
                                                class="block w-full text-left px-3 py-2 text-sm border-b border-gray-100">
                                            <span x-text="p.NmProd"></span> <span class="text-gray-400" x-text="'(' + p.KdProd + ')'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Judul</label>
                    <input type="text" :name="`items[${index}][Judul]`" x-model="item.Judul" maxlength="30" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <template x-if="!isJasaPotong(item.KdProd)">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Panjang <span x-text="dimensionUnit(item.KdProd)"></span></label>
                        <input type="number" step="0.01" :name="`items[${index}][Panjang]`" x-model="item.Panjang"
                               :readonly="!needsDimension(item.KdProd)" :required="needsDimension(item.KdProd)"
                               :class="!needsDimension(item.KdProd) && 'bg-gray-100 text-gray-400'"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </template>
                <template x-if="!isJasaPotong(item.KdProd)">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Lebar <span x-text="dimensionUnit(item.KdProd)"></span></label>
                        <input type="number" step="0.01" :name="`items[${index}][Lebar]`" x-model="item.Lebar"
                               :readonly="!needsDimension(item.KdProd)" :required="needsDimension(item.KdProd)"
                               :class="!needsDimension(item.KdProd) && 'bg-gray-100 text-gray-400'"
                               class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </template>
                <template x-if="isJasaPotong(item.KdProd)">
                    <div class="sm:col-span-4">
                        <label class="block text-xs text-gray-500 mb-1">Data Jasa Potong</label>
                        <button type="button" @click="potongModalIndex = index"
                                class="w-full px-3 py-1.5 text-sm rounded-md border border-gray-300 hover:bg-gray-100 text-left">
                            <template x-if="item.PisauTurun && item.JumlahKertas && item.TebalKertas">
                                <span class="text-gray-700">
                                    P:<span x-text="item.PisauTurun"></span> Q:<span x-text="item.JumlahKertas"></span> TB:<span x-text="item.TebalKertas"></span>
                                </span>
                            </template>
                            <template x-if="!(item.PisauTurun && item.JumlahKertas && item.TebalKertas)">
                                <span class="text-gray-400">Isi data potong…</span>
                            </template>
                        </button>
                        <input type="hidden" :name="`items[${index}][PisauTurun]`" :value="item.PisauTurun">
                        <input type="hidden" :name="`items[${index}][JumlahKertas]`" :value="item.JumlahKertas">
                        <input type="hidden" :name="`items[${index}][TebalKertas]`" :value="item.TebalKertas">
                        <input type="hidden" :name="`items[${index}][Panjang]`" value="0">
                        <input type="hidden" :name="`items[${index}][Lebar]`" value="0">
                    </div>
                </template>
                <div class="sm:col-span-1">
                    <label class="block text-xs text-gray-500 mb-1">Qty</label>
                    <input type="number" :name="`items[${index}][Qty]`" x-model="item.Qty" min="1" required
                           :readonly="isJasaPotong(item.KdProd)" :class="isJasaPotong(item.KdProd) && 'bg-gray-100 text-gray-400'"
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
        <button type="button" @click="addItem()"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
            + Tambah Item
        </button>
    </div>

    {{-- Modal: data Jasa Potong --}}
    <div x-show="potongModalIndex !== null" x-cloak @keydown.escape.window="potongModalIndex = null"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="potongModalIndex = null" class="absolute inset-0 bg-gray-900/50"></div>

        <template x-if="potongModalIndex !== null">
            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md" @click.stop>
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Data Jasa Potong</h3>
                    <button type="button" @click="potongModalIndex = null" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pisau Turun</label>
                        <input type="number" min="0" x-model.number="items[potongModalIndex].PisauTurun"
                               @input="syncPotongQty(potongModalIndex)"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Kertas</label>
                        <input type="number" min="0" x-model.number="items[potongModalIndex].JumlahKertas"
                               @input="syncPotongQty(potongModalIndex)"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tebal Kertas</label>
                        <input type="number" min="0" x-model.number="items[potongModalIndex].TebalKertas"
                               @input="syncPotongQty(potongModalIndex)"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="pt-3 border-t text-sm text-gray-600">
                        <p class="font-mono text-xs text-gray-400 mb-1">((P × Q × TB) / 10) + X</p>
                        Ongkos: <span class="font-semibold text-gray-900" x-text="'Rp ' + potongTotal(items[potongModalIndex]).toLocaleString('id-ID')"></span>
                    </div>

                    <div class="pt-2">
                        <button type="button" @click="potongModalIndex = null"
                                class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Selesai
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
