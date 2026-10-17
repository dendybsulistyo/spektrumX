{{-- Foto pribadi (maks. 1 MB). Dipotong persegi & dikecilkan di browser sebelum dikirim. --}}
@php($photoUser = auth()->user())
<section id="foto"
         x-data="photoForm({
             current: @js($photoUser->avatar_photo ? $photoUser->avatarUrl() : null),
             storeUrl: @js(route('avatar.photo.store')),
             destroyUrl: @js(route('avatar.photo.destroy')),
             maxBytes: {{ \App\Http\Controllers\AvatarController::PHOTO_MAX_KB * 1024 }},
         })">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Foto Pribadi</h2>
        <p class="mt-1 text-sm text-gray-600">Foto tampil di menu atas dan fitur Status, menggantikan avatar. Format JPG, PNG atau WEBP, <b>maksimal 1 MB</b>.</p>
    </header>

    <div class="ph-editor">
        <div class="ph-preview" :class="!(preview || current) && 'is-empty'">
            <template x-if="preview || current"><img :src="preview || current" alt="Foto pribadi"></template>
            <template x-if="!(preview || current)"><span>Belum ada foto</span></template>
        </div>

        <div class="ph-controls">
            <input type="file" x-ref="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pick($event)">
            <div class="ph-actions">
                <button type="button" class="av-btn av-btn-ghost" @click="$refs.file.click()" :disabled="saving">Pilih foto…</button>
                <button type="button" class="av-btn av-btn-primary" x-show="blob" @click="save()" :disabled="saving">
                    <span x-text="saving ? 'Mengunggah…' : 'Simpan foto'"></span>
                </button>
                <button type="button" class="av-btn av-btn-ghost" x-show="blob" @click="cancel()" :disabled="saving">Batal</button>
                <button type="button" class="av-btn av-btn-ghost ph-danger" x-show="current && !blob" @click="remove()" :disabled="saving">Hapus foto</button>
            </div>
            <p class="ph-hint" x-show="fileInfo" x-text="fileInfo"></p>
            <p class="ph-hint">Foto otomatis dipotong persegi (bagian tengah) agar pas di lingkaran.</p>
            <span class="av-msg" x-show="message" x-text="message" :class="error && 'is-error'"></span>
        </div>
    </div>
</section>

<style>
    .ph-editor { display: flex; flex-wrap: wrap; align-items: center; gap: 28px; margin-top: 18px; }
    .ph-preview { width: 140px; height: 140px; overflow: hidden; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 0 0 1px #e3ddcf, 0 10px 24px -14px rgba(27, 34, 54, .45); background: #f1ece0; display: flex; align-items: center; justify-content: center; }
    .ph-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ph-preview.is-empty span { font-size: 12px; color: #77736a; }
    .ph-controls { flex: 1; min-width: 260px; display: flex; flex-direction: column; gap: 8px; }
    .ph-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .ph-hint { font-size: 12.5px; color: #77736a; margin: 0; }
    .ph-danger { color: #b42318; }
    /* Tombol .av-btn juga dipakai di sini bila editor avatar tidak dimuat. */
    .av-btn { height: 38px; padding: 0 16px; font-size: 13.5px; font-weight: 600; border-radius: 8px; cursor: pointer; }
    .av-btn:disabled { opacity: .55; cursor: not-allowed; }
    .av-btn-primary { color: #fff; background: #1b2236; border: 1px solid #1b2236; }
    .av-btn-ghost { color: #1b2236; background: #fff; border: 1px solid #cfc7b5; }
    .av-msg { font-size: 13px; color: #2e7a4f; }
    .av-msg.is-error { color: #c8246c; }
</style>

<script>
    function photoForm(config) {
        const SIZE = 512;
        return {
            ...config,
            preview: null,
            blob: null,
            fileInfo: '',
            saving: false,
            message: '',
            error: false,
            mb(bytes) { return (bytes / 1024 / 1024).toFixed(2).replace('.', ',') + ' MB'; },
            pick(event) {
                const file = event.target.files[0];
                event.target.value = '';
                this.message = ''; this.error = false;
                if (!file) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    this.error = true; this.message = 'Foto harus berupa JPG, PNG atau WEBP.'; return;
                }
                if (file.size > this.maxBytes) {
                    this.error = true; this.message = `Ukuran foto ${this.mb(file.size)}, maksimal 1 MB. Pilih foto yang lebih kecil.`; return;
                }
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    // Potong persegi di tengah, kecilkan ke 512px, simpan sebagai JPG (data lokasi/EXIF ikut hilang).
                    const side = Math.min(img.naturalWidth, img.naturalHeight);
                    const canvas = document.createElement('canvas');
                    canvas.width = canvas.height = Math.min(SIZE, side);
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, (img.naturalWidth - side) / 2, (img.naturalHeight - side) / 2, side, side, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);
                    canvas.toBlob(blob => {
                        if (!blob) { this.error = true; this.message = 'Foto tidak bisa dibaca.'; return; }
                        this.blob = blob;
                        this.preview = canvas.toDataURL('image/jpeg', 0.88);
                        this.fileInfo = `${file.name} (${this.mb(file.size)}) → dikirim ${this.mb(blob.size)}`;
                    }, 'image/jpeg', 0.88);
                };
                img.onerror = () => { URL.revokeObjectURL(url); this.error = true; this.message = 'File bukan foto yang valid.'; };
                img.src = url;
            },
            cancel() { this.blob = null; this.preview = null; this.fileInfo = ''; this.message = ''; },
            updateNav(url) {
                document.querySelectorAll('[data-my-avatar]').forEach(el => {
                    if (url) { el.innerHTML = `<img src="${url}" alt="">`; } else { el.textContent = el.dataset.initials || ''; }
                });
            },
            save() {
                const form = new FormData();
                form.append('photo', this.blob, 'foto.jpg');
                this.saving = true; this.error = false;
                axios.post(this.storeUrl, form)
                    .then(r => {
                        this.current = r.data.url;
                        this.cancel();
                        this.message = 'Foto tersimpan ✓';
                        this.updateNav(r.data.url);
                    })
                    .catch(e => { this.error = true; this.message = e.response?.data?.errors?.photo?.[0] || e.response?.data?.message || 'Gagal mengunggah foto. Coba lagi.'; })
                    .finally(() => { this.saving = false; });
            },
            remove() {
                if (!confirm('Hapus foto pribadi?')) return;
                this.saving = true; this.error = false;
                axios.delete(this.destroyUrl)
                    .then(r => {
                        this.current = null;
                        this.message = 'Foto dihapus.';
                        this.updateNav(r.data.url);
                    })
                    .catch(() => { this.error = true; this.message = 'Gagal menghapus foto.'; })
                    .finally(() => { this.saving = false; });
            },
        };
    }
</script>
