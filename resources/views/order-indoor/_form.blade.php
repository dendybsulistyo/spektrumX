@php
    $order = $order ?? null;
    $initialItems = isset($items) && $items->isNotEmpty()
        ? $items->map(fn ($i) => ['KdProd' => $i->KdProd, 'Judul' => $i->Judul, 'Panjang' => $i->Panjang, 'Lebar' => $i->Lebar, 'Qty' => $i->Qty])->values()
        : collect([['KdProd' => '', 'Judul' => '', 'Panjang' => '', 'Lebar' => '', 'Qty' => 1]]);
@endphp

<div x-data="{ items: {{ old('items') ? json_encode(old('items')) : $initialItems->toJson() }} }">

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div>
            <x-input-label for="TglOrder" value="Tanggal Order" />
            <x-text-input id="TglOrder" name="TglOrder" type="date" class="mt-1 block w-full"
                value="{{ old('TglOrder', $order?->TglOrder ?? now()->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('TglOrder')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="KdCust" value="Customer" />
            <select id="KdCust" name="KdCust" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Pilih Customer --</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->KdCust }}" @selected(old('KdCust', $order?->KdCust) === $c->KdCust)>
                        {{ $c->NmCust }} ({{ $c->KdCust }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('KdCust')" class="mt-1" />
            @if ($customers->isEmpty())
                <p class="text-xs text-amber-600 mt-1">Belum ada data customer — tambahkan dulu di menu Customer.</p>
            @endif
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
            @if ($operators->isEmpty())
                <p class="text-xs text-amber-600 mt-1">Belum ada data operator di tabel operators.</p>
            @endif
        </div>
    </div>

    <div class="border-t pt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Item Order</h3>
            <button type="button" @click="items.push({ KdProd: '', Judul: '', Panjang: '', Lebar: '', Qty: 1 })"
                    class="text-xs px-3 py-1.5 bg-gray-100 rounded-md hover:bg-gray-200">+ Tambah Item</button>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-start mb-3 p-3 bg-gray-50 rounded-md">
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Produk</label>
                    <select :name="`items[${index}][KdProd]`" x-model="item.KdProd" required
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
                    <label class="block text-xs text-gray-500 mb-1">Panjang (m)</label>
                    <input type="number" step="0.01" :name="`items[${index}][Panjang]`" x-model="item.Panjang" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Lebar (m)</label>
                    <input type="number" step="0.01" :name="`items[${index}][Lebar]`" x-model="item.Lebar" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs text-gray-500 mb-1">Qty</label>
                    <input type="number" :name="`items[${index}][Qty]`" x-model="item.Qty" min="1" required
                           class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="col-span-2 sm:col-span-1 flex sm:items-end h-full pt-1 sm:pt-5">
                    <button type="button" @click="items.length > 1 && items.splice(index, 1)"
                            class="text-red-600 text-xs hover:underline">Hapus</button>
                </div>
            </div>
        </template>

        <x-input-error :messages="$errors->get('items')" class="mt-1" />
    </div>
</div>
