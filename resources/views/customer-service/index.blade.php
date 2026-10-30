<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Customer Service</h2>
            {{-- <p class="mt-1 text-sm text-slate-500">Info transfer untuk Kasir.</p> --}}
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    @can('customer-service.manage')
        {{-- Rapat ala dashboard operator: field kecil, sejajar dalam baris, tanpa header besar. --}}
        <style>
            .cs-compact .cs-f { display:flex; flex-direction:column; gap:3px; min-width:0; font-size:10.5px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:#64748b; }
            .cs-compact .cs-f .opt { font-weight:400; text-transform:none; letter-spacing:0; color:#94a3b8; }
            html body.font-sans .cs-compact .cs-in, .cs-compact .cs-in { height:32px !important; min-height:32px !important; line-height:30px; box-sizing:border-box; width:100%; padding:0 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px; font-weight:400; text-transform:none; letter-spacing:0; color:#0f172a; background:#fff; }
            html body.font-sans .cs-compact select.cs-in, .cs-compact select.cs-in { padding:0 28px 0 8px !important; line-height:30px !important; }
            .cs-compact .cs-in:focus { outline:none; border-color:#3156d3; box-shadow:0 0 0 2px rgba(49,86,211,.15); }
            html body.font-sans .cs-compact .cs-in, .cs-compact .cs-in { padding-top:0 !important; padding-bottom:0 !important; font-size:13px !important; }
            .cs-compact .cs-in[readonly] { background:#f1f5f9; color:#475569; }
            .cs-compact .cs-seg { display:flex; height:32px; }
            .cs-compact .cs-seg label { flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:0 10px; border:1px solid #cbd5e1; font-size:12.5px; font-weight:600; text-transform:none; letter-spacing:0; color:#334155; background:#fff; cursor:pointer; white-space:nowrap; }
            .cs-compact .cs-seg label + label { border-left:0; }
            .cs-compact .cs-seg label:first-child { border-radius:4px 0 0 4px; }
            .cs-compact .cs-seg label:last-child { border-radius:0 4px 4px 0; }
            .cs-compact .cs-seg input { position:absolute; opacity:0; pointer-events:none; }
            .cs-compact .cs-seg label:has(input:checked) { background:#1b2236; border-color:#1b2236; color:#fff; }
            .cs-compact .cs-grid { display:grid; gap:8px 10px; grid-template-columns:repeat(2, minmax(0,1fr)); }
            @media (min-width: 768px) { .cs-compact .cs-grid { grid-template-columns:repeat(4, minmax(0,1fr)); } }
            @media (min-width: 1280px) { .cs-compact .cs-grid { grid-template-columns:repeat(6, minmax(0,1fr)); } }
            .cs-compact .span-2 { grid-column:span 2; }
            .cs-compact .span-full { grid-column:1 / -1; }
        </style>
        <section class="cs-compact mb-4 rounded border border-slate-300 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2">
                <p class="text-[13px] font-semibold text-slate-900">Lembar Kerja CS <span class="font-normal text-slate-500">· Form Permintaan Produksi</span></p>
                <p class="hidden text-xs text-slate-500 sm:block">Informasi awal permintaan customer</p>
            </div>

            <form method="POST" action="{{ route('customer-service.job-sheet.store') }}"
                  x-data="{ orderType: @js(old('order_type', 'indoor')) }" class="p-3">
                @csrf
                <div class="cs-grid">
                    <div class="cs-f span-2 relative"
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
                               autocomplete="off" placeholder="Cari nama atau kode customer..." required class="cs-in">
                        <input type="hidden" name="customer_code" :value="selectedCode">
                        <div x-show="open && results.length" x-cloak
                             class="absolute top-full z-30 mt-1 max-h-60 w-full overflow-y-auto rounded border border-slate-200 bg-white py-1 text-sm font-normal normal-case tracking-normal shadow-xl">
                            <template x-for="customer in results" :key="customer.KdCust">
                                <button type="button" @click="select(customer)" class="block w-full px-3 py-1.5 text-left text-slate-700 hover:bg-blue-50">
                                    <span class="font-medium" x-text="customer.NmCust"></span>
                                    <span class="ml-1 text-xs text-slate-400" x-text="`(${customer.KdCust})`"></span>
                                    <span x-show="customer.Telp" class="block text-xs text-slate-400" x-text="customer.Telp"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="query && !selectedCode" class="text-[11px] font-normal normal-case tracking-normal text-amber-600">Pilih customer dari hasil pencarian.</p>
                        <x-input-error :messages="$errors->get('customer_code')" class="normal-case tracking-normal" />
                    </div>
                    <div class="cs-f">
                        <span>Tujuan Order</span>
                        <div class="cs-seg" role="radiogroup" aria-label="Tujuan order">
                            <label><input type="radio" name="order_type" value="indoor" x-model="orderType">Indoor</label>
                            <label><input type="radio" name="order_type" value="outdoor" x-model="orderType">Outdoor</label>
                        </div>
                        <x-input-error :messages="$errors->get('order_type')" class="normal-case tracking-normal" />
                    </div>
                    <label x-show="orderType === 'indoor'" x-cloak class="cs-f">
                        <span>Folder Indoor</span>
                        <select name="indoor_folder" :disabled="orderType !== 'indoor'" :required="orderType === 'indoor'" class="cs-in">
                            <option value="">Pilih folder...</option>
                            @foreach (\App\Models\CustomerServiceJobSheet::INDOOR_FOLDER_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected(old('indoor_folder') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('indoor_folder')" class="normal-case tracking-normal" />
                    </label>
                    <label class="cs-f">
                        <span>Tanggal Masuk</span>
                        <input type="date" name="received_at" value="{{ old('received_at', now()->format('Y-m-d')) }}" required class="cs-in">
                    </label>
                    <label class="cs-f">
                        <span>Deadline <span class="opt">(opsional)</span></span>
                        <input type="date" name="deadline" value="{{ old('deadline') }}" class="cs-in">
                    </label>

                    <label class="cs-f">
                        <span>Progress Hari</span>
                        <select name="progress_hari" required class="cs-in">
                            <option value="">Pilih...</option>
                            @for ($day = 1; $day <= 10; $day++)
                                <option value="{{ $day }}" @selected((string) old('progress_hari') === (string) $day)>{{ $day }} hari</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('progress_hari')" class="normal-case tracking-normal" />
                    </label>
                    <label class="cs-f">
                        <span>PC <span class="opt">(opsional)</span></span>
                        <input name="pc" value="{{ old('pc') }}" maxlength="100" class="cs-in">
                    </label>
                    <label class="cs-f span-2">
                        <span>Folder / File <span class="opt">(opsional)</span></span>
                        <input name="folder_file" value="{{ old('folder_file') }}" maxlength="255" class="cs-in">
                    </label>
                    <label class="cs-f">
                        <span>OPF <span class="opt">(opsional)</span></span>
                        <input name="opf" value="{{ old('opf') }}" maxlength="100" class="cs-in">
                    </label>
                    <label class="cs-f">
                        <span>Pembuat Order</span>
                        <input value="{{ auth()->user()->name }}" readonly class="cs-in">
                    </label>

                    <div class="span-full flex flex-wrap items-end gap-2">
                        <label class="cs-f" style="flex:1 1 320px;">
                            <span>Keterangan</span>
                            <input name="notes" value="{{ old('notes') }}" maxlength="1000" class="cs-in" placeholder="Catatan untuk produksi (opsional)">
                        </label>
                        <label class="flex h-8 items-center gap-2 whitespace-nowrap rounded border border-amber-200 bg-amber-50 px-3 font-semibold text-amber-800" style="font-size:12.5px;height:32px;">
                            <input type="checkbox" name="is_urgent" value="1" @checked(old('is_urgent'))
                                   class="rounded border-amber-400 text-amber-500 focus:ring-amber-500">
                            &#9733; Urgent <span class="font-normal text-amber-700" style="font-size:12px;">(order owner)</span>
                        </label>
                        <button type="submit" class="whitespace-nowrap rounded bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700" style="height:32px;font-size:13px;">Simpan Lembar Kerja</button>
                    </div>
                </div>
            </form>
        </section>
    @endcan

</x-app-layout>
