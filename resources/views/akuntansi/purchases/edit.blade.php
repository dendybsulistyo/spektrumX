<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Akuntansi · Pembelian</p>
            <h2 class="mt-1 text-xl font-semibold text-slate-900">Edit Pembelian {{ $purchase->nomor_bukti }}</h2>
        </div>
    </x-slot>

    @php
        $initialLines = old('lines', $purchase->lines->map(fn ($line) => [
            'deskripsi' => $line->deskripsi,
            'inventory_item_id' => $line->inventory_item_id ? (string) $line->inventory_item_id : '',
            'klasifikasi' => $line->klasifikasi,
            'qty' => $line->qty,
            'satuan' => $line->satuan,
            'harga_satuan' => $line->harga_satuan,
        ])->values()->all());
        $method = old('metode', $purchase->status === 'tunai' ? 'tunai' : 'hutang');
    @endphp

    <div class="py-7">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="text-sm text-slate-600">Perubahan akan membalik jurnal lama dan membuat jurnal baru secara otomatis.</p>
                </div>

                <form method="POST" action="{{ route('akuntansi.purchases.update', $purchase) }}"
                      class="grid gap-4 p-5 md:grid-cols-2"
                      x-data="{
                          lines: @js($initialLines),
                          angka(value) { return Number(String(value ?? '').replace(/\./g, '').replace(',', '.')) || 0 },
                          rupiah(value) { return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) },
                          subtotal(line) { return this.angka(line.qty) * this.angka(line.harga_satuan) },
                          totalRincian() { return this.lines.reduce((total, line) => total + this.subtotal(line), 0) }
                      }">
                    @csrf
                    @method('PUT')

                    <label class="text-sm text-slate-700">Supplier
                        <select name="supplier_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $supplier->id)>{{ $supplier->kode_bantu }} — {{ $supplier->nama }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">Tanggal Pembelian
                        <input name="tanggal" type="date" value="{{ old('tanggal', $purchase->tanggal->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                    </label>
                    <label class="text-sm text-slate-700">No. SJ / Faktur
                        <input name="nomor_bukti" value="{{ old('nomor_bukti', $purchase->nomor_bukti) }}" maxlength="50" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                    </label>
                    <label class="text-sm text-slate-700">Akun Pembelian / Beban
                        <select name="akun_pembelian" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->NoAkun }}" @selected(old('akun_pembelian', $purchase->akun_pembelian) === $account->NoAkun)>{{ $account->NoAkun }} — {{ $account->NmAkun }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700 md:col-span-2">Keterangan
                        <input name="keterangan" value="{{ old('keterangan', $purchase->keterangan) }}" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                    </label>

                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-medium text-slate-700">Rincian Barang <span class="font-normal text-slate-400">(opsional, harga sebelum PPN)</span></label>
                            <button type="button" @click="lines.push({ deskripsi: '', inventory_item_id: '', klasifikasi: '', qty: 1, satuan: 'pcs', harga_satuan: '' })" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">+ Tambah Baris</button>
                        </div>
                        <div class="mt-2 space-y-2">
                            <template x-for="(line, index) in lines" :key="index">
                                <div class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-6">
                                    <input :name="`lines[${index}][deskripsi]`" x-model="line.deskripsi" class="rounded border-slate-300 text-sm md:col-span-2" placeholder="Nama bahan / barang">
                                    <select :name="`lines[${index}][inventory_item_id]`" x-model="line.inventory_item_id" class="rounded border-slate-300 text-sm">
                                        <option value="">Master stok (opsional)</option>
                                        @foreach ($inventoryItems as $inventoryItem)
                                            <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->kode }} — {{ $inventoryItem->nama }}</option>
                                        @endforeach
                                    </select>
                                    <select :name="`lines[${index}][klasifikasi]`" x-model="line.klasifikasi" class="rounded border-slate-300 text-sm">
                                        <option value="">Klasifikasi</option>
                                        <option value="bahan_baku">Bahan baku</option>
                                        <option value="bahan_penolong">Bahan penolong</option>
                                        <option value="aset">Aset / disusutkan</option>
                                        <option value="biaya">Biaya</option>
                                    </select>
                                    <input :name="`lines[${index}][qty]`" x-model="line.qty" type="number" min="0.001" step="0.001" class="rounded border-slate-300 text-sm" placeholder="Qty">
                                    <input :name="`lines[${index}][harga_satuan]`" x-model="line.harga_satuan" type="number" min="0" step="1" data-rupiah-decimal class="rounded border-slate-300 text-sm" placeholder="Harga satuan">
                                    <input :name="`lines[${index}][satuan]`" x-model="line.satuan" class="rounded border-slate-300 text-sm" placeholder="Satuan">
                                    <div class="flex items-center justify-between md:col-span-5">
                                        <span class="text-xs text-slate-500">Subtotal: <strong x-text="'Rp '+rupiah(subtotal(line))"></strong></span>
                                        <button type="button" @click="lines.splice(index, 1)" class="text-xs font-semibold text-red-600">Hapus baris</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="lines.length" class="mt-2 text-sm font-semibold text-slate-700">Total rincian (DPP): <span x-text="'Rp '+rupiah(totalRincian())"></span></p>
                    </div>

                    <label class="text-sm text-slate-700">Total Nota bila tanpa rincian
                        <input name="total" type="number" min="1" step="1" data-rupiah-decimal value="{{ old('total', $purchase->lines->isEmpty() ? $purchase->total : '') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <div class="flex flex-col justify-end gap-2 pb-1 text-sm text-slate-700">
                        <label class="flex items-center gap-2"><input type="hidden" name="kena_ppn" value="0"><input type="checkbox" name="kena_ppn" value="1" @checked(old('kena_ppn', $purchase->ppn > 0)) class="rounded border-slate-300 text-indigo-600"> Termasuk PPN Masukan {{ rtrim(rtrim(number_format($taxRate, 2, '.', ''), '0'), '.') }}%</label>
                        <p class="text-xs text-slate-500">DPP dan PPN akan dihitung ulang saat disimpan.</p>
                    </div>
                    <label class="text-sm text-slate-700">Termin (hari)
                        <input name="termin_hari" type="number" min="0" max="3650" value="{{ old('termin_hari', $purchase->termin_hari) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <label class="text-sm text-slate-700">Tanggal Terima Invoice
                        <input name="tanggal_terima_invoice" type="date" value="{{ old('tanggal_terima_invoice', $purchase->tanggal_terima_invoice?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                    <fieldset class="md:col-span-2">
                        <legend class="text-sm font-medium text-slate-700">Metode Pembelian</legend>
                        <div class="mt-2 flex flex-wrap gap-5 text-sm">
                            <label><input type="radio" name="metode" value="tunai" @checked($method === 'tunai') class="text-indigo-600"> Tunai / langsung dibayar</label>
                            <label><input type="radio" name="metode" value="hutang" @checked($method === 'hutang') class="text-indigo-600"> Hutang supplier</label>
                        </div>
                    </fieldset>
                    <label class="text-sm text-slate-700">Cara bayar (untuk tunai)
                        <select name="cara_bayar" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @foreach (['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('cara_bayar', $purchase->cara_bayar ?: 'tunai') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">No. referensi (opsional)
                        <input name="no_referensi" value="{{ old('no_referensi', $purchase->no_referensi) }}" maxlength="50" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 md:col-span-2">
                        <a href="{{ route('akuntansi.purchases.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                        <button type="submit" onclick="return confirm('Simpan perubahan dan buat jurnal koreksi?')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Perubahan</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
