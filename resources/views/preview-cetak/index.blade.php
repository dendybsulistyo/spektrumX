<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Preview Cetak</h2>
    </x-slot>

    <div class="space-y-4"
         x-data="{
            media: 'kaos',
            designUrl: null,
            fileName: '',
            downloading: false,
            MEDIA: [
                { id: 'kaos', label: 'Kaos' },
                { id: 'mug', label: 'Mug' },
                { id: 'stiker', label: 'Stiker' },
                { id: 'kartu-nama', label: 'Kartu Nama' },
                { id: 'id-card', label: 'ID Card' },
                { id: 'spanduk', label: 'Spanduk' },
                { id: 'baliho', label: 'Baliho' },
            ],
            onFile(e) {
                const file = e.target.files[0];
                if (!file) return;
                this.fileName = file.name;
                const reader = new FileReader();
                reader.onload = (ev) => { this.designUrl = ev.target.result; };
                reader.readAsDataURL(file);
            },
            clearDesign() {
                this.designUrl = null;
                this.fileName = '';
                this.$refs.fileInput.value = '';
            },
            async download() {
                if (!this.designUrl) return;
                this.downloading = true;
                try {
                    const svg = this.$refs.stage.querySelector(`svg[data-mockup='${this.media}']`);
                    const scale = 2;
                    const vb = svg.viewBox.baseVal;

                    // Alpine leaves its raw `:href`/`x-*` directive attributes
                    // in the live DOM (it only toggles style for x-show, it
                    // doesn't strip the directive attributes themselves) — a
                    // bare `:href` isn't a legal XML attribute name, so the
                    // browser's SVG parser rejects the serialized markup
                    // outright when we try to load it as an <img> below.
                    // Clone and strip anything Alpine-related first.
                    const clone = svg.cloneNode(true);
                    clone.querySelectorAll('*').forEach((el) => {
                        [...el.attributes].forEach((attr) => {
                            if (attr.name.startsWith(':') || attr.name.startsWith('x-') || attr.name.startsWith('@')) {
                                el.removeAttribute(attr.name);
                            }
                        });
                    });
                    clone.removeAttribute('x-show');
                    clone.setAttribute('width', vb.width);
                    clone.setAttribute('height', vb.height);

                    const xml = new XMLSerializer().serializeToString(clone);
                    const svgBlob = new Blob([xml], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(svgBlob);
                    const img = new Image();
                    await new Promise((resolve, reject) => { img.onload = resolve; img.onerror = reject; img.src = url; });
                    const canvas = document.createElement('canvas');
                    canvas.width = vb.width * scale;
                    canvas.height = vb.height * scale;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);
                    const a = document.createElement('a');
                    a.download = `preview-${this.media}.png`;
                    a.href = canvas.toDataURL('image/png');
                    a.click();
                } finally {
                    this.downloading = false;
                }
            },
         }">

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500">
                Upload desain, pilih media, lihat hasilnya seolah-olah sudah dicetak/dipasang. Desain tidak diunggah ke server — semua diproses langsung di browser.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-4 items-start">
            {{-- Controls --}}
            <div class="space-y-4">
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pilih Media</p>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="m in MEDIA" :key="m.id">
                            <button type="button" @click="media = m.id"
                                    :class="media === m.id ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                                    class="px-3 py-2 rounded-md border text-xs font-medium text-center transition">
                                <span x-text="m.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Upload Desain</p>

                    <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 rounded-md py-6 px-3 cursor-pointer hover:border-gray-400 hover:bg-gray-50 transition text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class="text-xs text-gray-500">Klik untuk pilih gambar</span>
                        <input x-ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile($event)">
                    </label>

                    <template x-if="fileName">
                        <div class="mt-3 flex items-center justify-between gap-2 text-xs bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                            <span class="truncate text-gray-600" x-text="fileName"></span>
                            <button type="button" @click="clearDesign()" class="text-red-600 hover:underline shrink-0">Hapus</button>
                        </div>
                    </template>

                    <button type="button" @click="download()" :disabled="!designUrl || downloading"
                            class="mt-4 w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span x-show="!downloading">Unduh Gambar</span>
                        <span x-show="downloading">Menyiapkan...</span>
                    </button>
                </div>
            </div>

            {{-- Stage --}}
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div x-ref="stage" class="p-8 flex items-center justify-center min-h-[480px]"
                     style="background-image: linear-gradient(45deg, #f3f4f6 25%, transparent 25%), linear-gradient(-45deg, #f3f4f6 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f3f4f6 75%), linear-gradient(-45deg, transparent 75%, #f3f4f6 75%); background-size: 24px 24px; background-position: 0 0, 0 12px, 12px -12px, -12px 0;">

                    @include('preview-cetak.mockups.kaos')
                    @include('preview-cetak.mockups.mug')
                    @include('preview-cetak.mockups.stiker')
                    @include('preview-cetak.mockups.kartu-nama')
                    @include('preview-cetak.mockups.id-card')
                    @include('preview-cetak.mockups.spanduk')
                    @include('preview-cetak.mockups.baliho')
                </div>

                <template x-if="!designUrl">
                    <p class="text-center text-xs text-gray-400 pb-4">Upload desain di sebelah kiri untuk melihat preview.</p>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
