{{-- Editor avatar mini (Pria / Wanita, opsi berhijab). Library avatar hanya dimuat di halaman ini. --}}
@vite(['resources/js/avatar-editor.js'])

<section id="avatar"
         x-data="avatarForm({
             saved: @js(auth()->user()->avatar_options),
             storeUrl: @js(route('avatar.store')),
             destroyUrl: @js(route('avatar.destroy')),
         })"
         x-init="init()">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Avatar</h2>
        <p class="mt-1 text-sm text-gray-600">Avatar tampil di menu atas dan fitur Status. Pilih jenis, lalu acak atau atur sesuai selera.</p>
    </header>

    <div class="av-editor">
        <div class="av-preview-col">
            <div class="av-preview" x-html="svg"></div>
            <button type="button" class="av-btn av-btn-ghost" @click="shuffle()">🎲 Acak</button>
        </div>

        <div class="av-controls">
            <div class="av-row">
                <span class="av-label">Jenis</span>
                <div class="av-seg">
                    <button type="button" :class="o.gender === 'pria' && 'is-active'" @click="setGender('pria')">Pria</button>
                    <button type="button" :class="o.gender === 'wanita' && 'is-active'" @click="setGender('wanita')">Wanita</button>
                </div>
            </div>

            <div class="av-row" x-show="o.gender === 'wanita'">
                <span class="av-label">Berhijab</span>
                <div class="av-seg">
                    <button type="button" :class="!o.hijab && 'is-active'" @click="o.hijab = false; render()">Tidak</button>
                    <button type="button" :class="o.hijab && 'is-active'" @click="o.hijab = true; render()">Ya</button>
                </div>
            </div>

            <div class="av-row" x-show="!(o.gender === 'wanita' && o.hijab)">
                <span class="av-label">Gaya rambut</span>
                <div class="av-stepper">
                    <button type="button" @click="step('hair', -1)" aria-label="Sebelumnya">‹</button>
                    <span x-text="(hairList().indexOf(o.hair) + 1) + ' / ' + hairList().length"></span>
                    <button type="button" @click="step('hair', 1)" aria-label="Berikutnya">›</button>
                </div>
            </div>

            <div class="av-row" x-show="o.gender === 'wanita' && o.hijab">
                <span class="av-label">Warna hijab</span>
                <div class="av-swatches">
                    <template x-for="c in opts.hijabColors" :key="c">
                        <button type="button" class="av-swatch" :style="`background:${c}`" :class="o.hijabColor === c && 'is-active'" @click="o.hijabColor = c; render()"></button>
                    </template>
                </div>
            </div>

            <div class="av-row">
                <span class="av-label">Wajah</span>
                <div class="av-stepper-group">
                    <div class="av-stepper"><button type="button" @click="step('eyes', -1)">‹</button><span>Mata</span><button type="button" @click="step('eyes', 1)">›</button></div>
                    <div class="av-stepper"><button type="button" @click="step('mouth', -1)">‹</button><span>Senyum</span><button type="button" @click="step('mouth', 1)">›</button></div>
                    <div class="av-stepper"><button type="button" @click="step('eyebrows', -1)">‹</button><span>Alis</span><button type="button" @click="step('eyebrows', 1)">›</button></div>
                </div>
            </div>

            <div class="av-row">
                <span class="av-label">Aksesori</span>
                <div class="av-checks">
                    <label><input type="checkbox" x-model="o.glasses" @change="render()"> Kacamata</label>
                    <label x-show="o.gender === 'pria'"><input type="checkbox" x-model="o.beard" @change="render()"> Jenggot</label>
                </div>
            </div>

            <div class="av-row">
                <span class="av-label">Warna latar</span>
                <div class="av-swatches">
                    <template x-for="c in opts.backgrounds" :key="c">
                        <button type="button" class="av-swatch" :style="`background:#${c}`" :class="o.background === c && 'is-active'" @click="o.background = c; render()"></button>
                    </template>
                </div>
            </div>

            <div class="av-actions">
                <button type="button" class="av-btn av-btn-primary" @click="save()" :disabled="saving">
                    <span x-text="saving ? 'Menyimpan…' : 'Simpan avatar'"></span>
                </button>
                <button type="button" class="av-btn av-btn-ghost" x-show="hasSaved" @click="remove()" :disabled="saving">Pakai inisial saja</button>
                <span class="av-msg" x-show="message" x-text="message" :class="error && 'is-error'"></span>
            </div>
        </div>
    </div>
</section>

<style>
    .av-editor { display: flex; flex-wrap: wrap; gap: 28px; margin-top: 18px; }
    .av-preview-col { display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .av-preview { width: 180px; height: 180px; overflow: hidden; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 0 0 1px #e3ddcf, 0 10px 24px -14px rgba(27, 34, 54, .45); background: #f1ece0; }
    .av-preview svg { width: 100%; height: 100%; display: block; }
    .av-controls { flex: 1; min-width: 280px; display: flex; flex-direction: column; gap: 12px; }
    .av-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .av-label { width: 96px; font-size: 12px; font-weight: 600; color: #77736a; text-transform: uppercase; letter-spacing: .06em; }
    .av-seg { display: inline-flex; }
    .av-seg button { height: 34px; padding: 0 16px; font-size: 13px; font-weight: 600; color: #5d5a52; background: #fff; border: 1px solid #cfc7b5; border-right: 0; cursor: pointer; }
    .av-seg button:first-child { border-radius: 8px 0 0 8px; }
    .av-seg button:last-child { border-right: 1px solid #cfc7b5; border-radius: 0 8px 8px 0; }
    .av-seg button.is-active { color: #fff; background: #1b2236; border-color: #1b2236; }
    .av-stepper-group { display: flex; flex-wrap: wrap; gap: 8px; }
    .av-stepper { display: inline-flex; align-items: center; height: 34px; background: #fff; border: 1px solid #cfc7b5; border-radius: 8px; overflow: hidden; }
    .av-stepper button { width: 32px; height: 100%; font-size: 18px; color: #1b2236; background: transparent; border: 0; cursor: pointer; }
    .av-stepper button:hover { background: #f1ece0; }
    .av-stepper span { min-width: 58px; padding: 0 4px; font-size: 12.5px; text-align: center; color: #262a33; font-family: 'IBM Plex Mono', monospace; }
    .av-swatches { display: flex; flex-wrap: wrap; gap: 8px; }
    .av-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #cfc7b5; cursor: pointer; }
    .av-swatch.is-active { box-shadow: 0 0 0 2px #1b2236; transform: scale(1.1); }
    .av-checks { display: flex; gap: 16px; font-size: 13.5px; }
    .av-checks label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .av-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 6px; }
    .av-btn { height: 38px; padding: 0 16px; font-size: 13.5px; font-weight: 600; border-radius: 8px; cursor: pointer; }
    .av-btn:disabled { opacity: .55; cursor: not-allowed; }
    .av-btn-primary { color: #fff; background: #1b2236; border: 1px solid #1b2236; }
    .av-btn-ghost { color: #1b2236; background: #fff; border: 1px solid #cfc7b5; }
    .av-msg { font-size: 13px; color: #2e7a4f; }
    .av-msg.is-error { color: #c8246c; }
</style>

<script>
    function avatarForm(config) {
        return {
            ...config,
            opts: null,
            o: null,
            svg: '',
            saving: false,
            message: '',
            error: false,
            hasSaved: !!config.saved,
            init() {
                // Modul avatar dimuat terpisah (module script) — tunggu sampai siap.
                const ready = () => {
                    if (!window.SpektrumAvatar) return setTimeout(ready, 30);
                    this.opts = window.SpektrumAvatar.AVATAR_OPTIONS;
                    this.o = this.saved ? { ...this.saved } : window.SpektrumAvatar.randomAvatar('pria');
                    this.render();
                };
                ready();
            },
            hairList() {
                return this.o?.gender === 'wanita' ? this.opts.hairWanita : this.opts.hairPria;
            },
            render() {
                this.svg = window.SpektrumAvatar.renderAvatar(this.o);
                this.message = '';
            },
            setGender(gender) {
                if (this.o.gender === gender) return;
                this.o.gender = gender;
                this.o.hair = this.hairList()[0];
                if (gender === 'pria') { this.o.hijab = false; }
                else { this.o.beard = false; }
                this.render();
            },
            shuffle() {
                this.o = window.SpektrumAvatar.randomAvatar(this.o.gender, this.o.hijab, { hijabColor: this.o.hijab ? undefined : this.o.hijabColor, background: this.o.background });
                this.render();
            },
            step(key, dir) {
                const list = key === 'hair' ? this.hairList() : this.opts[key];
                const index = Math.max(0, list.indexOf(this.o[key]));
                this.o[key] = list[(index + dir + list.length) % list.length];
                this.render();
            },
            save() {
                this.saving = true;
                this.error = false;
                axios.post(this.storeUrl, { options: this.o, svg: this.svg })
                    .then(r => {
                        this.hasSaved = true;
                        this.message = 'Avatar tersimpan ✓';
                        document.querySelectorAll('[data-my-avatar]').forEach(el => { el.innerHTML = `<img src="${r.data.url}" alt="">`; });
                    })
                    .catch(() => { this.error = true; this.message = 'Gagal menyimpan avatar. Coba lagi.'; })
                    .finally(() => { this.saving = false; });
            },
            remove() {
                if (!confirm('Hapus avatar dan kembali memakai inisial?')) return;
                this.saving = true;
                axios.delete(this.destroyUrl)
                    .then(() => {
                        this.hasSaved = false;
                        this.message = 'Avatar dihapus, kembali ke inisial.';
                        document.querySelectorAll('[data-my-avatar]').forEach(el => { el.textContent = el.dataset.initials || ''; });
                    })
                    .catch(() => { this.error = true; this.message = 'Gagal menghapus avatar.'; })
                    .finally(() => { this.saving = false; });
            },
        };
    }
</script>
