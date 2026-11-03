{{--
    Daftar cepat (Ctrl+B / ⌘B): Order Outdoor yang belum dibayar, dengan rincian ukuran.
    Dipasang di navbar untuk user ber-akses (lihat UnpaidOutdoorOrderController::PERMISSIONS).
    Memakai gaya .cc-* dari <x-cari-cepat>.
--}}
<div x-data="belumBayarOutdoor(@js(route('unpaid-outdoor')))" @keydown.window="globalKey($event)">
    <template x-teleport="body">
        <div x-show="isOpen" x-cloak class="cc-overlay" @click.self="close()">
            <div class="cc-box bbo-box" @keydown.escape.prevent="close()">
                <div class="cc-input">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M3 8h14" stroke-linecap="round"/></svg>
                    <input x-ref="input" type="text" x-model="q" autocomplete="off" spellcheck="false"
                           placeholder="Outdoor belum bayar — saring No. Order, customer, atau nama file…">
                    <span class="cc-spin" x-show="loading"></span>
                    <button type="button" class="bbo-refresh" @click="load()" title="Muat ulang">↻</button>
                    <kbd>Esc</kbd>
                </div>

                <div class="cc-results">
                    <div class="bbo-summary" x-show="!loading && orders.length">
                        <span x-text="filtered.length + ' order · ' + filtered.reduce((n, o) => n + o.items.length, 0) + ' item'"></span>
                        <span>Total <strong x-text="rupiah(filtered.reduce((n, o) => n + o.total, 0))"></strong></span>
                    </div>
                    <template x-if="!loading && !filtered.length">
                        <div class="cc-empty" x-text="orders.length ? 'Tidak ada yang cocok dengan saringan.' : 'Tidak ada Order Outdoor yang belum dibayar.'"></div>
                    </template>

                    <template x-for="o in filtered" :key="o.id">
                        <div class="bbo-order">
                            <div class="bbo-head">
                                <span class="cc-type">OUT</span>
                                <div class="cc-main">
                                    <div><strong class="cc-mono" x-text="o.no_order"></strong> <span class="cc-pill" x-text="o.queue"></span></div>
                                    <div class="cc-muted"><span x-text="o.customer"></span> · <span x-text="o.date_label"></span></div>
                                </div>
                                <strong class="bbo-total" x-text="rupiah(o.total)"></strong>
                                <div class="cc-actions"><a x-show="o.kasir_url" :href="o.kasir_url">Kasir</a></div>
                            </div>
                            <table class="bbo-items">
                                <thead><tr><th>File / Item</th><th>Ukuran (cm)</th><th>Finishing</th><th class="r">Qty</th><th>Bahan</th><th>Printer</th><th class="r">Subtotal</th></tr></thead>
                                <tbody>
                                    <template x-for="(it, i) in o.items" :key="i">
                                        <tr>
                                            <td class="bbo-name" x-text="it.name"></td>
                                            <td class="cc-mono" x-text="size(it)"></td>
                                            <td x-text="it.finishing || '-'"></td>
                                            <td class="r" x-text="it.qty"></td>
                                            <td x-text="it.bahan || '-'"></td>
                                            <td x-text="it.printer || '-'"></td>
                                            <td class="r" x-text="rupiah(it.subtotal)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <p class="cc-hint" x-show="!loading && orders.length >= limit" x-text="'Menampilkan ' + limit + ' order terbaru.'"></p>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .bbo-box { width:min(980px, 100%); max-height:82vh; }
        .bbo-refresh { width:28px; height:28px; border:1px solid #e3ddcf; border-radius:6px; background:#fff; color:#5d5a52; cursor:pointer; }
        .bbo-summary { display:flex; justify-content:space-between; padding:8px 10px 6px; font-size:12.5px; color:#5d5a52; }
        .bbo-order { margin:6px 2px 10px; border:1px solid #e9e3d6; border-radius:10px; background:#fff; overflow:hidden; }
        .bbo-head { display:flex; align-items:center; gap:10px; padding:8px 10px; background:#faf7f0; border-bottom:1px solid #eee8db; }
        .bbo-total { margin-left:auto; font-size:13px; white-space:nowrap; color:#1b2236; }
        .bbo-items { width:100%; border-collapse:collapse; font-size:12.5px; }
        .bbo-items th { padding:5px 10px; color:#77736a; font-size:10.5px; font-weight:700; letter-spacing:.06em; text-align:left; text-transform:uppercase; }
        .bbo-items td { padding:5px 10px; border-top:1px solid #f1ece0; vertical-align:top; color:#262a33; }
        .bbo-items .r { text-align:right; white-space:nowrap; }
        .bbo-name { font-weight:600; overflow-wrap:anywhere; }
        @media (max-width:640px) { .bbo-items th:nth-child(5), .bbo-items td:nth-child(5), .bbo-items th:nth-child(6), .bbo-items td:nth-child(6) { display:none; } }
    </style>
    <script>
        function belumBayarOutdoor(url) {
            return {
                url,
                isOpen: false,
                loading: false,
                q: '',
                orders: [],
                limit: 0,
                get filtered() {
                    const q = this.q.trim().toLowerCase();
                    if (!q) return this.orders;
                    return this.orders.filter(o => o.no_order.toLowerCase().includes(q)
                        || o.customer.toLowerCase().includes(q)
                        || o.items.some(it => (it.name || '').toLowerCase().includes(q)));
                },
                globalKey(event) {
                    if ((event.ctrlKey || event.metaKey) && !event.shiftKey && !event.altKey && event.key.toLowerCase() === 'b') {
                        event.preventDefault();
                        this.isOpen ? this.close() : this.open();
                    }
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
                        .then(r => { this.orders = r.data.orders; this.limit = r.data.limit; })
                        .catch(() => {})
                        .finally(() => { this.loading = false; });
                },
                size(it) {
                    const fmt = v => (v === null || v === undefined || v === '') ? '-' : Number(v).toLocaleString('id-ID');
                    return fmt(it.panjang) + ' × ' + fmt(it.lebar);
                },
                rupiah(n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); },
            };
        }
    </script>
@endonce
