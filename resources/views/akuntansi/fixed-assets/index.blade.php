<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Laporan Penyusutan & Amortisasi Fiskal</h2>
    </x-slot>

    @php
        $fmt = fn ($value) => number_format((float) $value, 0, ',', '.');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Filter Tahun -->
            <section class="rounded-xl border bg-white p-5 shadow-sm">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <label class="text-sm text-gray-700 font-medium">
                        Tahun Laporan (Pajak)
                        <input type="number" name="tahun" value="{{ $tahun }}" min="2000" max="2100" 
                               class="mt-1 block w-32 rounded-lg border-gray-300 text-sm">
                    </label>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Tampilkan
                    </button>
                </form>
                <p class="mt-3 text-sm text-gray-500">
                    Penyusutan dihitung secara fiskal menggunakan metode Garis Lurus (Straight Line) dari bulan perolehan aset.
                </p>
            </section>

            <!-- Ringkasan Nilai Aset -->
            <section class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Perolehan Aset</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalHarga) }}</p>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Penyusutan Tahun {{ $tahun }}</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-700 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalDepreciationYear) }}</p>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Akumulasi Penyusutan</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalAccumulated) }}</p>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm bg-indigo-50 border-indigo-100">
                    <p class="text-xs text-indigo-600 font-medium uppercase tracking-wider">Total Nilai Sisa Buku</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalBookValue) }}</p>
                </div>
            </section>

            <!-- Form Tambah Aset (Hanya Pengaturan) -->
            @can('keuangan.pengaturan')
                <details class="rounded-xl border bg-white shadow-sm overflow-hidden" {{ $errors->any() ? 'open' : '' }}>
                    <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-indigo-700 bg-white hover:bg-gray-50 select-none">
                        + Tambah Catatan Aset Tetap Baru
                    </summary>
                    <form method="POST" action="{{ route('akuntansi.fixed-assets.store') }}" class="grid gap-3 border-t p-5 md:grid-cols-3 bg-gray-50/50">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Nama Barang / Aset</label>
                            <input name="nama" type="text" placeholder="Contoh: Komputer Desain Unit A" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Golongan / Kelompok Harta</label>
                            <select name="kelompok" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Pilih Kelompok Harta</option>
                                @foreach ($kelompokOptions as $key => $labelOpt)
                                    <option value="{{ $key }}">{{ $labelOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Tanggal Perolehan</label>
                            <input name="tanggal_perolehan" type="date" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div x-data="{ 
                            displayVal: '', 
                            rawVal: '{{ old('harga_perolehan') }}',
                            formatRupiah(val) {
                                if (!val) return '';
                                let number_string = val.toString().replace(/[^,\d]/g, '');
                                let split = number_string.split(',');
                                let sisa = split[0].length % 3;
                                let rupiah = split[0].substr(0, sisa);
                                let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                                
                                if (ribuan) {
                                    let separator = sisa ? '.' : '';
                                    rupiah += separator + ribuan.join('.');
                                }
                                
                                rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
                                return rupiah;
                            },
                            updateValue(e) {
                                let val = e.target.value;
                                this.rawVal = val.replace(/[^0-9]/g, '');
                                this.displayVal = this.formatRupiah(this.rawVal);
                            },
                            init() {
                                if (this.rawVal) {
                                    this.displayVal = this.formatRupiah(this.rawVal);
                                }
                            }
                        }">
                            <label class="block text-xs font-medium text-gray-600">Harga Perolehan (Rp)</label>
                            <input type="text" 
                                   x-model="displayVal" 
                                   @input="updateValue" 
                                   placeholder="Contoh: 150.000.000" 
                                   required
                                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                            <input type="hidden" name="harga_perolehan" :value="rawVal">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Metode Penyusutan</label>
                            <select name="metode" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <option value="garis_lurus">Garis Lurus (Straight Line)</option>
                                <option value="saldo_menurun" disabled>Saldo Menurun (Declining Balance) - Not Supported</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Keterangan / Lokasi</label>
                            <input name="keterangan" type="text" placeholder="Opsional"
                                   class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-3 flex justify-end pt-2">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Simpan Aset Tetap
                            </button>
                        </div>
                    </form>
                </details>
            @endcan

            <!-- Daftar Aset & Tabel Penyusutan -->
            <section class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="border-b px-5 py-4 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Daftar Penyusutan & Amortisasi Aset Tetap (Tahun Laporan: {{ $tahun }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="no-fixed-layout min-w-full text-sm">
                        <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-indigo-700">
                            <tr>
                                <th class="w-12 px-4 py-3 text-center">No</th>
                                <th class="px-5 py-3 text-left">Nama Aset</th>
                                <th class="px-5 py-3 text-left whitespace-nowrap">Golongan</th>
                                <th class="px-5 py-3 text-left whitespace-nowrap">Tgl Perolehan</th>
                                <th class="px-5 py-3 text-right whitespace-nowrap">Harga Perolehan</th>
                                <th colspan="2" class="px-5 py-2 text-center whitespace-nowrap">Tarif & Masa Manfaat</th>
                                <th class="px-5 py-3 text-right whitespace-nowrap">Penyusutan Tahun Ini</th>
                                <th class="px-5 py-3 text-right whitespace-nowrap">Akumulasi Penyusutan</th>
                                <th class="px-5 py-3 text-right">Nilai Sisa Buku</th>
                                @can('keuangan.pengaturan')
                                    <th class="px-5 py-3 text-center">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($assets as $index => $asset)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="w-12 px-4 py-3 text-center text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $asset->nama }}</p>
                                        @if($asset->keterangan)
                                            <p class="text-xs text-gray-400">{{ $asset->keterangan }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $asset::KELOMPOK_LABELS[$asset->kelompok] ?? $asset->kelompok }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $asset->tanggal_perolehan->translatedFormat('d-M-Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">
                                        Rp&nbsp;{{ $fmt($asset->harga_perolehan) }}
                                    </td>
                                    <td class="px-5 py-2 text-center text-gray-600">
                                        {{ $asset->rate * 100 }}%
                                    </td>
                                    <td class="px-5 py-2 text-center text-gray-600">
                                        {{ $asset->years }} Tahun
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-indigo-600 whitespace-nowrap">
                                        Rp&nbsp;{{ $fmt($asset->current_depreciation) }}
                                        @if($asset->current_months < 12 && $asset->current_months > 0)
                                            <span class="block text-[10px] font-normal text-gray-400">({{ $asset->current_months }} Bln)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-900 whitespace-nowrap">
                                        Rp&nbsp;{{ $fmt($asset->accumulated_depreciation) }}
                                        @if($asset->prior_months > 0)
                                            <span class="block text-[10px] font-normal text-gray-400">(Sdh susut {{ $asset->prior_months + $asset->current_months }} Bln)</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">
                                        Rp&nbsp;{{ $fmt($asset->book_value) }}
                                        @if($asset->book_value <= 0)
                                            <span class="block text-[10px] font-bold text-red-600 uppercase">Habis Masa Manfaat</span>
                                        @endif
                                    </td>
                                    @can('keuangan.pengaturan')
                                        <td class="px-5 py-3 text-center">
                                            <form method="POST" action="{{ route('akuntansi.fixed-assets.destroy', $asset) }}" 
                                                  onsubmit="return confirm('Hapus aset {{ $asset->nama }} dari daftar?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold text-xs">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-5 py-10 text-center text-gray-400">
                                        Belum ada aset tetap tercatat. Tambahkan aset baru menggunakan formulir di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($assets->isNotEmpty())
                            <tfoot class="bg-indigo-50/50 font-bold border-t border-indigo-100">
                                <tr>
                                    <td colspan="4" class="px-5 py-3 text-left text-indigo-900">TOTAL</td>
                                    <td class="px-5 py-3 text-right text-gray-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalHarga) }}</td>
                                    <td colspan="2"></td>
                                    <td class="px-5 py-3 text-right text-indigo-700 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalDepreciationYear) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalAccumulated) }}</td>
                                    <td class="px-5 py-3 text-right text-indigo-900 whitespace-nowrap">Rp&nbsp;{{ $fmt($totalBookValue) }}</td>
                                    @can('keuangan.pengaturan')
                                        <td></td>
                                    @endcan
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
