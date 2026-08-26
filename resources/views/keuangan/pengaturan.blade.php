<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Pengaturan Data GL</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-pengaturan-keuangan { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
        </style>
    @endpush

    <div id="industry-pengaturan-keuangan">
        <div style="max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <p class="text-muted" style="font-size: 14px; margin: 0;">
                    Data perusahaan untuk keperluan pajak — dipakai di kop laporan/faktur dan sebagai default tarif di Laporan PPN. Diisi sekali, berlaku untuk seluruh sistem.
                </p>
            </div>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="POST" action="{{ route('keuangan.pengaturan.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nama_perusahaan" value="Nama Perusahaan (resmi, sesuai NPWP)" />
                        <x-text-input id="nama_perusahaan" name="nama_perusahaan" type="text" class="mt-1 block w-full"
                            value="{{ old('nama_perusahaan', $pengaturan->nama_perusahaan) }}" maxlength="100" />
                        <x-input-error :messages="$errors->get('nama_perusahaan')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="alamat_perusahaan" value="Alamat Perusahaan" />
                        <x-text-input id="alamat_perusahaan" name="alamat_perusahaan" type="text" class="mt-1 block w-full"
                            value="{{ old('alamat_perusahaan', $pengaturan->alamat_perusahaan) }}" maxlength="255" />
                        <x-input-error :messages="$errors->get('alamat_perusahaan')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="npwp_perusahaan" value="NPWP Perusahaan" />
                        <x-text-input id="npwp_perusahaan" name="npwp_perusahaan" type="text" class="mt-1 block w-full"
                            value="{{ old('npwp_perusahaan', $pengaturan->npwp_perusahaan) }}" maxlength="20" placeholder="mis. 01.234.567.8-901.000" />
                        <x-input-error :messages="$errors->get('npwp_perusahaan')" class="mt-1" />
                    </div>

                    <div class="border-t pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_pkp" value="1" id="is_pkp"
                                   {{ old('is_pkp', $pengaturan->is_pkp) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-sm font-medium text-gray-700">Sudah PKP (Pengusaha Kena Pajak)</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-1">Kalau belum PKP, seharusnya tidak memungut PPN dari customer.</p>
                    </div>

                    <div>
                        <x-input-label for="tarif_ppn_default" value="Tarif PPN Default (%)" />
                        <x-text-input id="tarif_ppn_default" name="tarif_ppn_default" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full max-w-xs"
                            value="{{ old('tarif_ppn_default', $pengaturan->tarif_ppn_default) }}" required />
                        <x-input-error :messages="$errors->get('tarif_ppn_default')" class="mt-1" />
                        <p class="text-xs text-gray-400 mt-1">Dipakai sebagai tarif awal saat membuka Laporan PPN.</p>
                    </div>

                    <div>
                        <x-input-label for="nomor_seri_faktur_terakhir" value="Nomor Seri Faktur Pajak Terakhir (opsional)" />
                        <x-text-input id="nomor_seri_faktur_terakhir" name="nomor_seri_faktur_terakhir" type="text" class="mt-1 block w-full"
                            value="{{ old('nomor_seri_faktur_terakhir', $pengaturan->nomor_seri_faktur_terakhir) }}" maxlength="20" />
                        <p class="text-xs text-gray-400 mt-1">Catatan manual nomor terakhir yang dipakai, supaya tidak dobel saat menerbitkan faktur pajak berikutnya.</p>
                    </div>

                    <div class="pt-2 flex gap-3 border-t">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
