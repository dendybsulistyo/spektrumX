{{--
    Pencarian cepat (Ctrl+K / ⌘K): order (No. Order / SO / Invoice) & customer.
    Dipasang sekali di navbar (layouts/app.blade.php).
--}}
<div x-data="cariCepat(@js(route('quick-search')))" @keydown.window="globalKey($event)" class="relative">
    <button type="button" class="cc-trigger" @click="open()" title="Pencarian cepat (Ctrl+K)">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="M13.2 13.2L17 17" stroke-linecap="round"/></svg>
        <span>Cari…</span>
        <kbd x-text="isMac ? '⌘K' : 'Ctrl K'"></kbd>
    </button>

    <template x-teleport="body">
        <div x-show="isOpen" x-cloak class="cc-overlay" @click.self="close()">
            <div class="cc-box" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape.prevent="detail ? detail = null : close()">
                <div class="cc-input">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="M13.2 13.2L17 17" stroke-linecap="round"/></svg>
                    <input x-ref="input" type="text" x-model="q" @input.debounce.300ms="search()" autocomplete="off" spellcheck="false"
                           placeholder="Cari No. Order, SO, Invoice, atau nama customer…">
                    <span class="cc-spin" x-show="loading"></span>
                    <kbd>Esc</kbd>
                </div>

                {{-- Riwayat proses satu order --}}
                <template x-if="detail">
                    <div class="cc-detail">
                        <div class="cc-detail-head">
                            <button type="button" class="cc-back" @click="detail = null">&larr; Kembali</button>
                            <strong class="cc-mono" x-text="detail.no_order"></strong>
                            <span x-text="detail.customer || ''"></span>
                        </div>
                        <template x-if="detail.loading"><div class="cc-empty">Memuat riwayat…</div></template>
                        <template x-if="!detail.loading">
                            <div class="cc-detail-body">
                                <div class="cc-label">Posisi qty saat ini</div>
                                <template x-for="item in detail.items" :key="item.id">
                                    <div class="cc-item">
                                        <strong x-text="item.name || '-'"></strong>
                                        <span class="cc-mono" x-text="'Qty ' + item.qty_total"></span>
                                        <div class="cc-pills">
                                            <template x-for="stage in item.stages.filter(s => s.qty > 0)" :key="stage.label">
                                                <span class="cc-pill" x-text="stage.qty + ' di ' + stage.label"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div class="cc-label" style="margin-top:12px;">Riwayat proses (terbaru dulu)</div>
                                <template x-for="(h, i) in detail.history" :key="i">
                                    <div class="cc-history">
                                        <span class="cc-dot"></span>
                                        <div><strong x-text="h.stage"></strong> · <span x-text="h.qty !== null ? h.qty + ' unit' : h.action"></span>
                                            <div class="cc-muted" x-show="h.catatan" x-text="h.catatan"></div></div>
                                        <div class="cc-muted cc-right"><div class="cc-mono" x-text="h.created_at"></div><div x-text="h.user"></div></div>
                                    </div>
                                </template>
                                <template x-if="!detail.history.length"><div class="cc-empty">Belum ada riwayat.</div></template>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Hasil pencarian --}}
                <div class="cc-results" x-show="!detail">
                    <template x-if="q.trim().length < 3">
                        <div class="cc-hint">
                            Ketik minimal 3 huruf. Contoh: <code>IND.2.2610</code> · <code>26100800056</code> · <code>INV.2.</code> · <code>SO.2.</code> · nama customer
                            <div class="cc-keys"><kbd>↑</kbd><kbd>↓</kbd> pilih &nbsp; <kbd>Enter</kbd> buka &nbsp; <kbd>Esc</kbd> tutup</div>
                        </div>
                    </template>
                    <template x-if="q.trim().length >= 3 && !loading && !items.length">
                        <div class="cc-empty">Tidak ada hasil untuk “<span x-text="q.trim()"></span>”.</div>
                    </template>

                    <template x-if="orders.length"><div class="cc-group">Order</div></template>
                    <template x-for="(o, i) in orders" :key="'o' + o.type + o.id">
                        <div class="cc-row" :class="active === i && 'is-active'" @mouseenter="active = i" @click="choose(i)">
                            <span class="cc-type" x-text="o.type === 'indoor' ? 'IND' : (o.type === 'outdoor' ? 'OUT' : 'ART')"></span>
                            <div class="cc-main">
                                <div><strong class="cc-mono" x-text="o.no_order"></strong>
                                    <span class="cc-muted cc-mono" x-show="o.invoice" x-text="'· ' + o.invoice"></span></div>
                                <div class="cc-muted"><span x-text="o.customer"></span> · <span x-text="o.date_label"></span></div>
                            </div>
                            <span class="cc-pill" x-text="statusLabel(o.status)"></span>
                            <span class="cc-pill" :class="'pay-' + o.status_bayar" x-text="payLabel(o.status_bayar)"></span>
                            <div class="cc-actions">
                                <button type="button" @click.stop="openDetail(o)" title="Lihat riwayat proses">Riwayat</button>
                                <a x-show="o.nota_url" :href="o.nota_url" @click.stop title="Lihat nota">Nota</a>
                                <a x-show="o.edit_url" :href="o.edit_url" @click.stop title="Buka order">Buka</a>
                            </div>
                        </div>
                    </template>

                    <template x-if="customers.length"><div class="cc-group">Customer</div></template>
                    <template x-for="(c, j) in customers" :key="'c' + c.code">
                        <div class="cc-row" :class="active === orders.length + j && 'is-active'" @mouseenter="active = orders.length + j" @click="choose(orders.length + j)">
                            <span class="cc-type cc-type-cust" x-text="(c.name || '?').trim().charAt(0).toUpperCase()"></span>
                            <div class="cc-main">
                                <div><strong x-text="c.name"></strong></div>
                                <div class="cc-muted"><span class="cc-mono" x-text="c.code"></span><span x-show="c.phone" x-text="' · ' + c.phone"></span></div>
                            </div>
                            <div class="cc-actions">
                                <a x-show="c.url" :href="c.url" @click.stop>Buka</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .cc-trigger { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 8px 0 10px; margin-right: 10px; font-size: 12.5px; color: #77736a; background: #fff; border: 1px solid #e3ddcf; border-radius: 8px; cursor: pointer; }
        .cc-trigger:hover { color: #1b2236; border-color: #cfc7b5; }
        .cc-trigger svg { width: 15px; height: 15px; }
        .cc-trigger kbd, .cc-input kbd, .cc-keys kbd { padding: 1px 6px; font-family: 'IBM Plex Mono', monospace; font-size: 10.5px; color: #5d5a52; background: #f1ece0; border: 1px solid #e3ddcf; border-radius: 4px; }
        .cc-overlay { position: fixed; inset: 0; z-index: 9990; display: flex; justify-content: center; align-items: flex-start; padding: 10vh 16px 16px; background: rgba(27, 34, 54, .5); }
        .cc-box { width: min(760px, 100%); max-height: 76vh; display: flex; flex-direction: column; overflow: hidden; background: #fffdf8; border: 1px solid #e3ddcf; border-radius: 14px; box-shadow: 0 30px 60px -20px rgba(0, 0, 0, .45); font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #262a33; }
        .cc-input { position: relative; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px dashed #cfc7b5; }
        .cc-input > svg { width: 19px; height: 19px; color: #77736a; flex: 0 0 auto; }
        .cc-input input { flex: 1; height: 34px; padding: 0; font: inherit; font-size: 16px; color: #1b2236; background: transparent !important; border: 0 !important; outline: none; box-shadow: none !important; }
        .cc-spin { width: 16px; height: 16px; border: 2px solid #e3ddcf; border-top-color: #0f8fb3; border-radius: 50%; animation: cc-spin .7s linear infinite; }
        @keyframes cc-spin { to { transform: rotate(360deg); } }
        .cc-results, .cc-detail { overflow-y: auto; padding: 6px 8px 10px; }
        .cc-hint { padding: 18px 12px; font-size: 13px; color: #77736a; line-height: 1.8; }
        .cc-hint code { padding: 1px 6px; font-family: 'IBM Plex Mono', monospace; font-size: 12px; color: #1b2236; background: #f1ece0; border-radius: 4px; }
        .cc-keys { margin-top: 6px; font-size: 12px; }
        .cc-empty { padding: 18px 12px; font-size: 13px; color: #77736a; text-align: center; }
        .cc-group { padding: 10px 10px 4px; font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #77736a; }
        .cc-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 9px; cursor: pointer; }
        .cc-row.is-active { background: #f1ece0; }
        .cc-type { flex: 0 0 auto; display: flex; width: 36px; height: 36px; align-items: center; justify-content: center; font-family: 'IBM Plex Mono', monospace; font-size: 10.5px; font-weight: 600; color: #fff; background: #1b2236; border-radius: 8px; }
        .cc-type-cust { font-family: 'IBM Plex Sans', sans-serif; font-size: 14px; background: #0f8fb3; border-radius: 50%; }
        .cc-main { flex: 1; min-width: 0; font-size: 13.5px; }
        .cc-main > div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cc-mono { font-family: 'IBM Plex Mono', monospace; font-variant-numeric: tabular-nums; }
        .cc-muted { color: #77736a; font-size: 12px; }
        .cc-pill { flex: 0 0 auto; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #5d5a52; background: #fff; border: 1px solid #e3ddcf; border-radius: 999px; white-space: nowrap; }
        .cc-pill.pay-lunas { color: #2e7a4f; background: #e6f2ea; border-color: transparent; }
        .cc-pill.pay-dp { color: #0b6a86; background: #e2f1f7; border-color: transparent; }
        .cc-pill.pay-hutang { color: #a3124a; background: #fbe6ef; border-color: transparent; }
        .cc-pill.pay-belum_bayar { color: #8a6400; background: #fdf2d3; border-color: transparent; }
        .cc-actions { display: flex; gap: 4px; flex: 0 0 auto; }
        .cc-actions a, .cc-actions button { padding: 4px 9px; font-size: 12px; font-weight: 600; color: #1b2236; text-decoration: none; background: #fff; border: 1px solid #cfc7b5; border-radius: 6px; cursor: pointer; }
        .cc-actions a:hover, .cc-actions button:hover { color: #fff; background: #1b2236; border-color: #1b2236; }
        .cc-detail-head { display: flex; align-items: center; gap: 10px; padding: 8px 6px 10px; border-bottom: 1px solid #e3ddcf; font-size: 13px; }
        .cc-back { padding: 4px 9px; font-size: 12px; font-weight: 600; color: #1b2236; background: #f1ece0; border: 0; border-radius: 6px; cursor: pointer; }
        .cc-detail-body { padding: 8px 6px; }
        .cc-label { margin-bottom: 6px; font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #77736a; }
        .cc-item { padding: 8px 10px; margin-bottom: 6px; font-size: 13px; background: #fff; border: 1px solid #e3ddcf; border-radius: 8px; }
        .cc-item > span { margin-left: 6px; color: #77736a; font-size: 12px; }
        .cc-pills { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
        .cc-history { display: flex; gap: 10px; padding: 7px 4px; font-size: 13px; border-bottom: 1px dashed #e3ddcf; }
        .cc-dot { flex: 0 0 auto; width: 8px; height: 8px; margin-top: 6px; background: #0f8fb3; border-radius: 50%; }
        .cc-right { margin-left: auto; text-align: right; white-space: nowrap; }
    </style>
    <script>
        function cariCepat(url) {
            return {
                url,
                isOpen: false,
                q: '',
                loading: false,
                orders: [],
                customers: [],
                active: 0,
                detail: null,
                requestId: 0,
                isMac: /Mac|iPhone|iPad/.test(navigator.platform),
                get items() { return [...this.orders, ...this.customers]; },
                globalKey(event) {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                        event.preventDefault();
                        this.isOpen ? this.close() : this.open();
                    }
                },
                open() {
                    this.isOpen = true;
                    this.detail = null;
                    this.$nextTick(() => { this.$refs.input.focus(); this.$refs.input.select(); });
                },
                close() { this.isOpen = false; this.detail = null; },
                search() {
                    const q = this.q.trim();
                    if (q.length < 3) { this.orders = []; this.customers = []; this.loading = false; return; }
                    const id = ++this.requestId;
                    this.loading = true;
                    axios.get(this.url, { params: { q } })
                        .then(r => {
                            if (id !== this.requestId) return;
                            this.orders = r.data.orders;
                            this.customers = r.data.customers;
                            this.active = 0;
                        })
                        .catch(() => {})
                        .finally(() => { if (id === this.requestId) this.loading = false; });
                },
                move(step) {
                    if (this.detail || !this.items.length) return;
                    this.active = (this.active + step + this.items.length) % this.items.length;
                    this.$nextTick(() => this.$root.querySelector('.cc-row.is-active')?.scrollIntoView({ block: 'nearest' }));
                },
                choose(index = this.active) {
                    if (this.detail) return;
                    if (index < this.orders.length) {
                        const o = this.orders[index];
                        if (!o) return;
                        if (o.edit_url) return window.location.href = o.edit_url;
                        if (o.nota_url) return window.location.href = o.nota_url;
                        return this.openDetail(o);
                    }
                    const c = this.customers[index - this.orders.length];
                    if (c?.url) window.location.href = c.url;
                },
                openDetail(o) {
                    this.detail = { no_order: o.no_order, customer: o.customer, loading: true, items: [], history: [] };
                    axios.get(o.progress_url)
                        .then(r => { this.detail = { ...r.data, customer: r.data.customer || o.customer, loading: false }; })
                        .catch(() => { this.detail = { ...this.detail, loading: false }; });
                },
                statusLabel(s) {
                    return ({ baru: 'Baru', desain: 'Desain', cetak: 'Cetak', finishing: 'Finishing', qc: 'Back Office', bungkus: 'Bungkus', siap_diambil: 'Siap Diambil', selesai: 'Selesai', batal: 'Batal' })[s] || (s || '-');
                },
                payLabel(s) {
                    return ({ lunas: 'Lunas', dp: 'DP', hutang: 'Hutang', belum_bayar: 'Belum Bayar' })[s] || (s || '-');
                },
            };
        }
    </script>
@endonce
