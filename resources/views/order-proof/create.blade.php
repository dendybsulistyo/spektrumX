<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Buat Order Proof</h2>
    </x-slot>

    @php
        $blankItem = ['NmFile' => '', 'kode' => '', 'Panjang' => '', 'Lebar' => '', 'Qty' => 1, 'catatan' => ''];
    @endphp

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Proof = contoh kecil untuk membayangkan hasil akhir. <strong>Tagihan Rp 0</strong>, tidak masuk kasir/laporan, langsung menjadi perintah Operator Layout.
            Setelah Layout menekan <em>Selesai</em>, proof muncul di daftar <kbd>Ctrl+X</kbd> untuk dibuatkan order reguler dengan ukuran pasti.
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('order-proof.store') }}"
              x-data="{
                  type: @js(old('order_type', 'outdoor')),
                  items: @js(old('items', [$blankItem])),
                  options: @js(['outdoor' => $outdoorOptions, 'indoor' => $indoorOptions, 'artwork' => $artworkOptions]),
                  query: '',
                  kdCust: @js(old('KdCust', '')),
                  results: [],
                  open: false,
                  timer: null,
                  search() {
                      this.kdCust = '';
                      clearTimeout(this.timer);
                      this.timer = setTimeout(async () => {
                          if (this.query.trim().length < 2) { this.results = []; return; }
                          const res = await fetch(`/customers-search?vip=1&q=${encodeURIComponent(this.query)}`);
                          this.results = res.ok ? await res.json() : [];
                          this.open = true;
                      }, 250);
                  },
                  pick(c) { this.kdCust = c.KdCust; this.query = `${c.NmCust} (${c.KdCust})`; this.open = false; },
                  addItem() { this.items.push(@js($blankItem)); },
                  removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
              }"
              x-init="if (kdCust) query = kdCust">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label value="Tipe proof" />
                    <div class="mt-1 flex gap-2">
                        @foreach (\App\Models\OrderProof::TYPE_LABELS as $key => $label)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                   :class="type === '{{ $key }}' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300'">
                                <input type="radio" name="order_type" value="{{ $key }}" x-model="type" @change="items.forEach(i => i.kode = '')" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="relative" @click.outside="open = false">
                    <x-input-label for="proofCustomer" value="Customer" />
                    <input type="text" id="proofCustomer" x-model="query" @input="search()" autocomplete="off" required
                           placeholder="Cari customer VIP (nama atau kode)..."
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <input type="hidden" name="KdCust" :value="kdCust">
                    <div x-show="open && results.length" x-cloak class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-white shadow-lg">
                        <template x-for="c in results" :key="c.KdCust">
                            <button type="button" @click="pick(c)" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100">
                                <span x-text="c.NmCust"></span> <span class="text-gray-400" x-text="'(' + c.KdCust + ')'"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="query && !kdCust" x-cloak class="mt-1 text-xs text-amber-700">Pilih customer dari daftar. Hanya customer VIP yang bisa dibuatkan proof.</p>
                </div>
            </div>

            <div class="mt-6">
                <x-input-label value="Item proof" />
                <p class="text-xs text-gray-500">Ukuran = perkiraan ukuran akhir, boleh dikosongkan bila belum pasti. Ukuran proof (mini) tulis di catatan.</p>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-2 py-2">Nama file / judul</th>
                                <th class="px-2 py-2" x-text="type === 'outdoor' ? 'Printer · Bahan' : 'Produk'"></th>
                                <th class="px-2 py-2 w-24">Panjang</th>
                                <th class="px-2 py-2 w-24">Lebar</th>
                                <th class="px-2 py-2 w-20">Qty</th>
                                <th class="px-2 py-2">Catatan</th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="border-b">
                                    <td class="px-2 py-1"><input type="text" :name="`items[${index}][NmFile]`" x-model="item.NmFile" maxlength="255" class="w-full rounded-md border-gray-300 text-sm"></td>
                                    <td class="px-2 py-1">
                                        <select :name="`items[${index}][kode]`" x-model="item.kode" class="w-full rounded-md border-gray-300 text-sm">
                                            <option value="">— belum dipilih —</option>
                                            <template x-for="(label, code) in options[type]" :key="code">
                                                <option :value="code" x-text="label" :selected="item.kode === code"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1"><input type="number" step="0.01" min="0" :name="`items[${index}][Panjang]`" x-model="item.Panjang" class="w-full rounded-md border-gray-300 text-sm"></td>
                                    <td class="px-2 py-1"><input type="number" step="0.01" min="0" :name="`items[${index}][Lebar]`" x-model="item.Lebar" class="w-full rounded-md border-gray-300 text-sm"></td>
                                    <td class="px-2 py-1"><input type="number" min="1" required :name="`items[${index}][Qty]`" x-model="item.Qty" class="w-full rounded-md border-gray-300 text-sm"></td>
                                    <td class="px-2 py-1"><input type="text" :name="`items[${index}][catatan]`" x-model="item.catatan" maxlength="255" placeholder="mis. proof A4" class="w-full rounded-md border-gray-300 text-sm"></td>
                                    <td class="px-2 py-1 text-center"><button type="button" @click="removeItem(index)" class="text-red-600 hover:underline" x-show="items.length > 1">&times;</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="button" @click="addItem()" class="mt-2 text-sm font-semibold text-indigo-700 hover:underline">+ Tambah item</button>
            </div>

            <div class="mt-6">
                <x-input-label for="catatan" value="Catatan untuk Operator Layout" />
                <textarea id="catatan" name="catatan" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 text-sm">{{ old('catatan') }}</textarea>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" :disabled="!kdCust" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50">Simpan &amp; kirim ke Layout</button>
                <a href="{{ route('order-proof.index') }}" class="text-sm text-gray-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
