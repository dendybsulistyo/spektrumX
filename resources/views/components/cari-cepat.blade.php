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
                            <a x-show="detail.proof" :href="detail.proof?.url" class="cc-muted cc-mono" x-text="'dari ' + detail.proof?.no_order"></a>
                        </div>
                        <template x-if="detail.loading"><div class="cc-empty">Memuat riwayat…</div></template>
                        <template x-if="!detail.loading">
                            <div class="cc-detail-body">
                                <div x-show="detail.team && detail.team.length" style="margin-bottom:14px;">
                                    <div class="cc-label">Operator per tahap</div>
                                    <div class="cc-team-grid">
                                        <template x-for="group in (detail.team || [])" :key="group.stage">
                                            <div class="cc-team-card">
                                                <div class="cc-team-stage" x-text="group.stage + (group.members.length > 1 ? ' · ' + group.members.length + ' operator' : '')"></div>
                                                <template x-for="member in group.members" :key="member.name">
                                                    <div class="cc-team-line"><span x-text="member.name"></span><span class="cc-mono cc-muted" x-text="member.qty + ' qty'"></span></div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="cc-label">Item &amp; posisi qty saat ini</div>
                                <template x-for="item in detail.items" :key="item.id">
                                    <div class="cc-item">
                                        <div class="cc-item-name">
                                            <strong x-text="item.name || '-'"></strong>
                                            <template x-if="item.file"><span x-text="' · ' + item.file"></span></template>
                                            <span class="cc-mono" x-text="' · Qty ' + item.qty_total"></span>
                                        </div>
                                        <template x-if="item.specs && item.specs.length">
                                            <dl class="cc-specs">
                                                <template x-for="spec in item.specs" :key="spec.label">
                                                    <div><dt x-text="spec.label"></dt><dd x-text="spec.value"></dd></div>
                                                </template>
                                            </dl>
                                        </template>
                                        <div class="cc-pills">
                                            <template x-for="stage in item.stages.filter(s => s.qty > 0)" :key="stage.label">
                                                <span class="cc-pill" x-text="stage.qty + ' di ' + stage.label"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div class="cc-label" style="margin-top:14px;">Riwayat proses (terbaru dulu)</div>
                                <template x-for="(h, i) in detail.history" :key="i">
                                    <div class="cc-history">
                                        <div class="cc-rail">
                                            <div class="cc-icon" :style="`border-color:${historyIconFor(h).color}; background:color-mix(in srgb, ${historyIconFor(h).color} 12%, white); color:${historyIconFor(h).color};`">
                                                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" x-html="historyIcons[historyIconFor(h).icon]"></svg>
                                            </div>
                                            <template x-if="i < detail.history.length - 1"><div class="cc-line"></div></template>
                                        </div>
                                        <div class="cc-history-body">
                                            <div>
                                                <div><strong x-text="h.stage"></strong> <span class="cc-muted">&middot;</span> <span x-text="h.qty !== null ? h.qty + ' unit' : h.action"></span><span class="cc-muted" x-show="h.action === 'selesai'"> (tuntas di tahap ini)</span></div>
                                                <div class="cc-muted" x-show="h.catatan" x-text="h.catatan"></div>
                                            </div>
                                            <div class="cc-muted cc-right"><div class="cc-mono" x-text="h.created_at"></div><div x-text="h.user"></div></div>
                                        </div>
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
                            Ketik minimal 3 huruf. Contoh: <code>IND.2.2610</code> · <code>26100800056</code> · <code>INV.2.</code> · <code>SO.2.</code> · <code>PRF.1.</code> · nama customer
                            <div class="cc-keys">
                                <kbd>↑</kbd><kbd>↓</kbd> pilih &nbsp; <kbd>Enter</kbd> buka &nbsp; <kbd>Esc</kbd> tutup
                                @if (collect(\App\Http\Controllers\UnpaidOutdoorOrderController::PERMISSIONS)->contains(fn ($permission) => auth()->user()?->hasPermission($permission)))
                                    &nbsp; <kbd x-text="isMac ? '⌘B' : 'Ctrl B'"></kbd> Outdoor belum bayar
                                @endif
                                @if (auth()->user()?->hasPermission('order-proof.manage'))
                                    &nbsp; <kbd x-text="isMac ? '⌘X' : 'Ctrl X'"></kbd> Proof siap dibuatkan order
                                @endif
                            </div>
                        </div>
                    </template>
                    <template x-if="q.trim().length >= 3 && !loading && !items.length">
                        <div class="cc-empty">Tidak ada hasil untuk “<span x-text="q.trim()"></span>”.</div>
                    </template>

                    <template x-if="orders.length"><div class="cc-group">Order</div></template>
                    <template x-for="(o, i) in orders" :key="'o' + o.type + o.id">
                        <div class="cc-row" :class="active === i && 'is-active'" @mouseenter="active = i" @click="choose(i)">
                            <span class="cc-type" x-text="({ indoor: 'IND', outdoor: 'OUT', artwork: 'ART', proof: 'PRF' })[o.type]"></span>
                            <div class="cc-main">
                                <div><span class="cc-late" x-show="o.late" :class="o.late?.ongoing && 'is-ongoing'" :title="o.late?.label"></span><strong class="cc-mono" x-text="o.no_order"></strong>
                                    <span class="cc-muted cc-mono" x-show="o.invoice" x-text="'· ' + o.invoice"></span>
                                    <span class="cc-muted cc-mono" x-show="o.proof" x-text="'· dari ' + o.proof"></span></div>
                                <div class="cc-muted"><span x-text="o.customer"></span> · <span x-text="o.date_label"></span><span class="cc-late-text" x-show="o.late" x-text="' · ' + o.late?.label"></span></div>
                            </div>
                            <span class="cc-pill" x-text="statusLabel(o.status)"></span>
                            <span class="cc-pill" x-show="o.type !== 'proof'" :class="'pay-' + o.status_bayar" x-text="payLabel(o.status_bayar)"></span>
                            <div class="cc-actions">
                                <button type="button" x-show="o.progress_url" @click.stop="openDetail(o)" title="Lihat riwayat proses">Riwayat</button>
                                <a x-show="o.nota_url" :href="o.nota_url" @click.stop title="Lihat nota">Nota</a>
                                <a x-show="o.proforma_url" :href="o.proforma_url" target="_blank" @click.stop title="Cetak Proforma Invoice tagihan VIP">Proforma</a>
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
        .cc-item-name { margin-bottom: 4px; }
        .cc-item-name span { color: #77736a; }
        .cc-specs { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 4px 14px; margin: 2px 0 8px; padding: 6px 8px; background: #faf7f0; border-radius: 6px; }
        .cc-specs dt { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #77736a; }
        .cc-specs dd { margin: 0; font-size: 13px; overflow-wrap: anywhere; }
        .cc-team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; }
        .cc-team-card { padding: 7px 9px; background: #fff; border: 1px solid #e3ddcf; border-radius: 6px; }
        .cc-team-stage { margin-bottom: 3px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #1b2236; }
        .cc-team-line { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; }
        .cc-pills { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
        .cc-history { display: flex; gap: 12px; font-size: 13px; }
        .cc-rail { display: flex; flex-direction: column; align-items: center; width: 28px; flex-shrink: 0; }
        .cc-icon { display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; flex-shrink: 0; border: 1.5px solid; border-radius: 9999px; }
        .cc-line { width: 2px; flex: 1; min-height: 26px; background: #e3ddcf; }
        .cc-history-body { flex: 1; display: flex; justify-content: space-between; gap: 14px; padding-bottom: 16px; }
        .cc-late { display: inline-block; width: 8px; height: 8px; margin-right: 6px; vertical-align: 1px; background: #d64545; border-radius: 50%; }
        .cc-late.is-ongoing { box-shadow: 0 0 0 3px rgba(214, 69, 69, .2); animation: cc-pulse 1.4s ease-in-out infinite; }
        @keyframes cc-pulse { 50% { box-shadow: 0 0 0 5px rgba(214, 69, 69, .05); } }
        .cc-late-text { color: #b23a3a; }
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
                historyIcons: {
                    kasir:  '<rect x=\'2\' y=\'6\' width=\'16\' height=\'9\' rx=\'1.3\'/><circle cx=\'10\' cy=\'10.5\' r=\'2\'/>',
                    pencil: '<path d=\'M3 17l1-4.2L12.8 4l3.2 3.2L7.2 16 3 17z\'/><path d=\'M11.3 5.5l3.2 3.2\'/>',
                    printer:'<rect x=\'4\' y=\'7\' width=\'12\' height=\'6\' rx=\'1\'/><path d=\'M6.5 7V3.5h7V7\'/><path d=\'M6.5 16.5h7V19h-7z\'/>',
                    scissors:'<circle cx=\'5\' cy=\'5\' r=\'2\'/><circle cx=\'5\' cy=\'15\' r=\'2\'/><path d=\'M6.4 6.4L17 17\'/><path d=\'M6.4 13.6L17 3\'/>',
                    shield: '<path d=\'M10 2.5l6.5 2.7v4.3c0 4.6-3.1 6.9-6.5 7.5-3.4-.6-6.5-2.9-6.5-7.5V5.2L10 2.5z\'/><path d=\'M7 10l2 2 4-4\'/>',
                    box:    '<path d=\'M3 6.3L10 3l7 3.3-7 3.2-7-3.2z\'/><path d=\'M3 6.3v7.4L10 17l7-3.3V6.3\'/><path d=\'M10 9.5V17\'/>',
                    checkcircle: '<circle cx=\'10\' cy=\'10\' r=\'7.2\'/><path d=\'M6.5 10.3l2.4 2.4 4.6-4.6\'/>',
                    bag:    '<path d=\'M5.3 7h9.4l-.9 9.3a1.1 1.1 0 01-1.1 1H7.3a1.1 1.1 0 01-1.1-1L5.3 7z\'/><path d=\'M7.7 7V5.3a2.3 2.3 0 014.6 0V7\'/>',
                    x:      '<circle cx=\'10\' cy=\'10\' r=\'7.2\'/><path d=\'M7.5 7.5l5 5\'/><path d=\'M12.5 7.5l-5 5\'/>',
                    rotate: '<path d=\'M5.5 4.8v4h4\'/><path d=\'M5.6 8.8a5.6 5.6 0 115.4 6.7\'/>',
                    check:  '<path d=\'M4.5 10.3l3.5 3.5 7.5-7.5\'/>',
                    clock:  '<circle cx=\'10\' cy=\'10\' r=\'7.2\'/><path d=\'M10 6.2v4l3 1.8\'/>',
                    dot:    '<circle cx=\'10\' cy=\'10\' r=\'2.2\'/>',
                },
                historyIconFor(h) {
                    const byAction = {
                        dibatalkan: { icon: 'x', color: '#c8246c' },
                        ditolak: { icon: 'x', color: '#c8246c' },
                        diulang: { icon: 'rotate', color: '#b07d00' },
                        disetujui: { icon: 'check', color: '#2e8b57' },
                        diajukan: { icon: 'clock', color: '#77736a' },
                        nota_pengganti: { icon: 'kasir', color: '#1b2236' },
                    };
                    const byStage = {
                        kasir: { icon: 'kasir', color: '#1b2236' },
                        desain: { icon: 'pencil', color: '#0f8fb3' },
                        cetak: { icon: 'printer', color: '#1b2236' },
                        finishing: { icon: 'scissors', color: '#b07d00' },
                        qc: { icon: 'shield', color: '#7a5cc4' },
                        bungkus: { icon: 'box', color: '#c8246c' },
                        siap_diambil: { icon: 'checkcircle', color: '#16857a' },
                        selesai: { icon: 'bag', color: '#2e8b57' },
                        pembatalan: { icon: 'x', color: '#c8246c' },
                    };
                    return byAction[h.action] || byStage[h.stage_key] || { icon: 'dot', color: '#77736a' };
                },
                payLabel(s) {
                    return ({ lunas: 'Lunas', dp: 'DP', hutang: 'Hutang', belum_bayar: 'Belum Bayar' })[s] || (s || '-');
                },
            };
        }
    </script>
@endonce
