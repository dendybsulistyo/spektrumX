<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Data Bahan Indoor</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{
            rows: @js($produk->getCollection()->map(fn ($p) => [
                'id' => $p->id,
                'KdProd' => $p->KdProd,
                'KdDivs' => $p->KdDivs,
                'NmDivs' => $p->kategori?->NmDivs,
                'NmProd' => $p->NmProd,
                'NoUrut' => $p->NoUrut,
                'HargaStd' => $p->HargaStd,
                'HargaMin' => $p->HargaMin,
                'Satuan' => $p->Satuan,
                'isPjLb' => $p->isPjLb,
                'isHPilih' => $p->isHPilih,
            ])),
            kategoriList: @js($kategoriList->map(fn ($k) => ['KdDivs' => $k->KdDivs, 'NmDivs' => $k->NmDivs])),
            pjLbLabels: @js(\App\Models\Produk::PJLB_LABELS),
            pjLbStyles: @js(\App\Models\Produk::PJLB_BADGE_STYLES),
            pjLbBadgeText: @js(\App\Models\Produk::PJLB_BADGE_TEXT),
            bertingkat: @js($bertingkat->map(fn ($tiers) => $tiers->map(fn ($t) => [
                'BatasA' => $t->BatasA,
                'BatasZ' => $t->BatasZ,
                'Harga' => $t->Harga,
            ]))),
            modalOpen: false,
            saving: false,
            errors: {},
            form: { bertingkat: [] },
            rupiah(value) { return value === null || value === '' ? '' : Number(value).toLocaleString('id-ID'); },
            angka(value) { return Number(String(value).replace(/\D/g, '')) || 0; },
            openEdit(row) {
                this.form = { ...row };
                this.form.bertingkat = (this.bertingkat[row.KdProd] || []).map(t => ({ ...t }));
                this.errors = {};
                this.modalOpen = true;
            },
            addTier() {
                this.form.bertingkat.push({ BatasA: null, BatasZ: null, Harga: null });
            },
            removeTier(idx) {
                this.form.bertingkat.splice(idx, 1);
            },
            async save() {
                this.saving = true;
                this.errors = {};
                try {
                    const res = await fetch(`/produk/${this.form.id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'X-HTTP-Method-Override': 'PUT',
                        },
                        body: JSON.stringify(this.form),
                    });
                    if (res.status === 422) {
                        const data = await res.json();
                        this.errors = data.errors || {};
                        this.saving = false;
                        return;
                    }
                    if (!res.ok) throw new Error('Gagal menyimpan');
                    const data = await res.json();
                    const p = data.produk;
                    const idx = this.rows.findIndex(r => r.id === p.id);
                    this.rows[idx] = {
                        id: p.id, KdProd: p.KdProd, KdDivs: p.KdDivs, NmDivs: p.kategori?.NmDivs,
                        NmProd: p.NmProd, NoUrut: p.NoUrut, HargaStd: p.HargaStd, HargaMin: p.HargaMin,
                        Satuan: p.Satuan, isPjLb: p.isPjLb, isHPilih: p.isHPilih,
                    };
                    if (this.form.KdProd !== p.KdProd) {
                        delete this.bertingkat[this.form.KdProd];
                    }
                    this.bertingkat[p.KdProd] = (data.bertingkat || []).map(t => ({ BatasA: t.BatasA, BatasZ: t.BatasZ, Harga: t.Harga }));
                    this.modalOpen = false;
                } catch (e) {
                    alert('Gagal menyimpan perubahan.');
                } finally {
                    this.saving = false;
                }
            },
         }">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode produk..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Cari</button>
                @if (request('search'))
                    <a href="{{ route('detail-indoor.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-[13px] min-w-[900px]">
            <thead class="bg-gray-900 text-white text-xs uppercase">
                <tr>
                    <th class="px-3 py-2 text-left w-16">No. Urut</th>
                    <th class="px-3 py-2 text-left w-20">Kode</th>
                    <th class="px-3 py-2 text-left">Divisi</th>
                    <th class="px-3 py-2 text-left">Produk</th>
                    <th class="px-3 py-2 text-right">Harga Std</th>
                    <th class="px-3 py-2 text-right">Harga Min</th>
                    <th class="px-3 py-2 text-left">Satuan</th>
                    <th class="px-3 py-2 text-center">Pj x Lebar</th>
                    <th class="px-3 py-2 text-center">Pil Harga</th>
                    <th class="px-3 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <template x-if="rows.length === 0">
                    <tr>
                        <td colspan="10" class="px-4 py-6 text-center text-gray-400">Belum ada data produk.</td>
                    </tr>
                </template>
                <template x-for="(row, idx) in rows" :key="row.id">
                    <tr :class="idx % 2 === 1 ? 'bg-gray-50' : ''">
                        <td class="px-3 py-2 text-gray-500" x-text="row.NoUrut"></td>
                        <td class="px-3 py-2 font-medium text-gray-900" x-text="row.KdProd"></td>
                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap" x-text="row.NmDivs ?? '-'"></td>
                        <td class="px-3 py-2 text-blue-700" x-text="row.NmProd"></td>
                        <td class="px-3 py-2 text-right text-gray-700" x-text="Number(row.HargaStd).toLocaleString('id-ID')"></td>
                        <td class="px-3 py-2 text-right text-gray-700" x-text="Number(row.HargaMin).toLocaleString('id-ID')"></td>
                        <td class="px-3 py-2 text-gray-600" x-text="row.Satuan"></td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full text-xs font-semibold whitespace-nowrap"
                                  :style="pjLbStyles[row.isPjLb] || 'background-color:#e5e7eb;color:#1f2937;border:1px solid #9ca3af'"
                                  :title="pjLbLabels[row.isPjLb]" x-text="pjLbBadgeText[row.isPjLb] || row.isPjLb"></span>
                        </td>
                        <td class="px-3 py-2 text-center text-gray-600" x-text="row.isHPilih === 1 ? 'Ya' : 'Tidak'"></td>
                        <td class="px-3 py-2 text-right">
                            @can('produk.manage')
                                <button type="button" @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                    Edit Harga
                                </button>
                            @endcan
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $produk->links() }}
        </div>

        <!-- Modal: edit produk -->
        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col" @click.stop>
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                    <h3 class="font-semibold text-gray-900">Edit Produk</h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="save" class="overflow-y-auto p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kode Produk</label>
                            <input type="text" x-model="form.KdProd" maxlength="4" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-red-600" x-show="errors.KdProd" x-text="errors.KdProd?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Urut</label>
                            <input type="number" x-model.number="form.NoUrut" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-red-600" x-show="errors.NoUrut" x-text="errors.NoUrut?.[0]"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select x-model="form.KdDivs"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Belum ada kategori --</option>
                            <template x-for="k in kategoriList" :key="k.KdDivs">
                                <option :value="k.KdDivs" x-text="k.NmDivs"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.KdDivs" x-text="errors.KdDivs?.[0]"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Produk</label>
                        <input type="text" x-model="form.NmProd" maxlength="30" required
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-red-600" x-show="errors.NmProd" x-text="errors.NmProd?.[0]"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Standar (Rp)</label>
                            <input type="text" inputmode="numeric" :value="rupiah(form.HargaStd)" @input="form.HargaStd = angka($event.target.value)" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-red-600" x-show="errors.HargaStd" x-text="errors.HargaStd?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Minimum (Rp)</label>
                            <input type="text" inputmode="numeric" :value="rupiah(form.HargaMin)" @input="form.HargaMin = angka($event.target.value)" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-red-600" x-show="errors.HargaMin" x-text="errors.HargaMin?.[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Satuan</label>
                            <input type="text" x-model="form.Satuan" maxlength="8" placeholder="m2, pcs, dll" required
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-red-600" x-show="errors.Satuan" x-text="errors.Satuan?.[0]"></p>
                        </div>
                    </div>

                    <div class="border-t pt-4 space-y-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cara Hitung Harga Order</label>
                            <select x-model.number="form.isPjLb"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <template x-for="(label, code) in pjLbLabels" :key="code">
                                    <option :value="Number(code)" x-text="code + ' — ' + label"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-red-600" x-show="errors.isPjLb" x-text="errors.isPjLb?.[0]"></p>
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :checked="form.isHPilih === 1" @change="form.isHPilih = $event.target.checked ? 1 : 2"
                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Pakai harga bertingkat sesuai jumlah qty</span>
                        </label>
                    </div>

                    <div class="border-t pt-4" x-show="form.isHPilih === 1">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-700">Harga Bertingkat — <span x-text="form.KdProd"></span></h4>
                            <button type="button" @click="addTier()" class="text-xs text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                + Tambah Tingkat
                            </button>
                        </div>
                        <template x-for="key in Object.keys(errors).filter(k => k.startsWith('bertingkat'))" :key="key">
                            <p class="mt-1 text-xs text-red-600" x-text="errors[key][0]"></p>
                        </template>

                        <template x-if="form.bertingkat.length === 0">
                            <p class="text-xs text-gray-400">Belum ada tingkatan harga. Klik "+ Tambah Tingkat" untuk menambahkan.</p>
                        </template>

                        <div class="space-y-2" x-show="form.bertingkat.length > 0">
                            <div class="grid grid-cols-12 gap-2 text-xs uppercase text-gray-500 px-1">
                                <div class="col-span-3">Dari Qty</div>
                                <div class="col-span-3">Sampai Qty <span class="normal-case text-gray-400">(0 = tak terbatas)</span></div>
                                <div class="col-span-4">Harga (Rp)</div>
                                <div class="col-span-2"></div>
                            </div>
                            <template x-for="(tier, tIdx) in form.bertingkat" :key="tIdx">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <div class="col-span-3">
                                        <input type="number" min="0" x-model.number="tier.BatasA"
                                               class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="number" min="0" x-model.number="tier.BatasZ"
                                               class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-4">
                                        <input type="text" inputmode="numeric" :value="rupiah(tier.Harga)" @input="tier.Harga = angka($event.target.value)"
                                               class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="col-span-2 text-right">
                                        <button type="button" @click="removeTier(tIdx)" class="text-red-600 hover:text-red-800" title="Hapus tingkat">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3 border-t">
                        <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50">
                            <span x-show="!saving">Simpan Perubahan</span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
