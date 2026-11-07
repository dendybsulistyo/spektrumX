{{--
    Daftar cepat (Ctrl+X / ⌘X): order proof yang sudah selesai di Layout, menunggu dibuatkan order reguler.
    Ctrl+X hanya membuka daftar bila tidak sedang mengetik dan tidak ada teks terpilih, supaya potong (cut) tetap jalan.
    Memakai gaya .cc-* dari <x-cari-cepat> dan .bbo-* dari <x-belum-bayar-outdoor>.
--}}
<div x-data="proofSiap(@js(route('order-proof.ready')))" @keydown.window="globalKey($event)">
    <template x-teleport="body">
        <div x-show="isOpen" x-cloak class="cc-overlay" @click.self="close()">
            <div class="cc-box bbo-box" @keydown.escape.prevent="close()">
                <div class="cc-input">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="12" height="14" rx="2"/><path d="M7 8h6M7 11h4" stroke-linecap="round"/></svg>
                    <input x-ref="input" type="text" x-model="q" autocomplete="off" spellcheck="false"
                           placeholder="Proof siap dibuatkan order — saring No. Proof, customer, atau nama file…">
                    <span class="cc-spin" x-show="loading"></span>
                    <button type="button" class="bbo-refresh" @click="load()" title="Muat ulang">↻</button>
                    <kbd>Esc</kbd>
                </div>
                <div class="cc-results">
                    <template x-if="!loading && !filtered.length">
                        <div class="cc-empty" x-text="proofs.length ? 'Tidak ada yang cocok dengan saringan.' : 'Tidak ada proof yang menunggu dibuatkan order.'"></div>
                    </template>
                    <template x-for="p in filtered" :key="p.id">
                        <div class="bbo-order">
                            <div class="bbo-head">
                                <span class="cc-type">PRF</span>
                                <div class="cc-main">
                                    <div><strong class="cc-mono" x-text="p.no_order"></strong> <span class="cc-pill" x-text="p.type"></span></div>
                                    <div class="cc-muted"><span x-text="p.customer"></span> · selesai <span x-text="p.done_label"></span></div>
                                </div>
                                <div class="cc-actions">
                                    <a :href="p.show_url">Detail</a>
                                    <button type="button" @click="closeProof(p)">Tutup</button>
                                    <a :href="p.order_url">Buat order</a>
                                </div>
                            </div>
                            <table class="bbo-items">
                                <thead><tr><th>File / Judul</th><th>Produk / Bahan</th><th>Perkiraan ukuran</th><th class="r">Qty</th></tr></thead>
                                <tbody>
                                    <template x-for="(it, i) in p.items" :key="i">
                                        <tr>
                                            <td class="bbo-name" x-text="it.file"></td>
                                            <td x-text="it.produk"></td>
                                            <td class="cc-mono" x-text="it.ukuran"></td>
                                            <td class="r" x-text="it.qty"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        /* Sama dengan gaya Ctrl+B; ditulis ulang karena pemegang proof belum tentu punya akses Ctrl+B. */
        .bbo-box { width:min(980px, 100%); max-height:82vh; }
        .bbo-refresh { width:28px; height:28px; border:1px solid #e3ddcf; border-radius:6px; background:#fff; color:#5d5a52; cursor:pointer; }
        .bbo-order { margin:6px 2px 10px; border:1px solid #e9e3d6; border-radius:10px; background:#fff; overflow:hidden; }
        .bbo-head { display:flex; align-items:center; gap:10px; padding:8px 10px; background:#faf7f0; border-bottom:1px solid #eee8db; }
        .bbo-items { width:100%; border-collapse:collapse; font-size:12.5px; }
        .bbo-items th { padding:5px 10px; color:#77736a; font-size:10.5px; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
        .bbo-items td { padding:5px 10px; border-top:1px solid #f1ece0; vertical-align:top; color:#262a33; overflow-wrap:anywhere; }
        .bbo-items .r { text-align:right; white-space:nowrap; }
        .bbo-name { font-weight:600; }
    </style>
    <script>
        function proofSiap(url) {
            return {
                url,
                isOpen: false,
                loading: false,
                q: '',
                proofs: [],
                get filtered() {
                    const q = this.q.trim().toLowerCase();
                    if (!q) return this.proofs;
                    return this.proofs.filter(p => p.no_order.toLowerCase().includes(q)
                        || p.customer.toLowerCase().includes(q)
                        || p.items.some(it => (it.file || '').toLowerCase().includes(q)));
                },
                globalKey(event) {
                    if (!(event.ctrlKey || event.metaKey) || event.shiftKey || event.altKey || event.key.toLowerCase() !== 'x') return;
                    if (!this.isOpen) {
                        const el = document.activeElement;
                        const typing = el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
                        if (typing || String(window.getSelection() || '').length) return;
                    }
                    event.preventDefault();
                    this.isOpen ? this.close() : this.open();
                },
                open() {
                    this.isOpen = true;
                    this.load();
                    this.$nextTick(() => this.$refs.input.focus());
                },
                close() { this.isOpen = false; },
                load() {
                    this.loading = true;
                    axios.get(this.url)
                        .then(r => { this.proofs = r.data.proofs; })
                        .catch(() => {})
                        .finally(() => { this.loading = false; });
                },
                closeProof(p) {
                    const alasan = prompt(`Tutup proof ${p.no_order}? Tulis alasan (customer tidak jadi order, dll.):`);
                    if (!alasan || !alasan.trim()) return;
                    axios.post(p.close_url, { alasan }, { headers: { Accept: 'application/json' } })
                        .then(() => { this.proofs = this.proofs.filter(x => x.id !== p.id); })
                        .catch(e => alert(e.response?.data?.message || 'Gagal menutup proof.'));
                },
            };
        }
    </script>
@endonce
