{{--
    Tema percobaan "Ruang Cetak" — latar kertas, tinta navy, aksen CMYK.
    Berlaku di semua antrean operator (Layout, Cetak, Finishing, QC, Bungkus,
    Pengambilan) lewat <x-operator-workspace-styles />.
    Untuk membatalkan: hapus baris <x-tema-ruang-cetak /> di
    resources/views/components/operator-workspace-styles.blade.php.
--}}
{{-- Font IBM Plex disimpan lokal di public/fonts (klien tanpa internet), dimuat lewat fonts.css di layout. --}}
<style>
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) {
        --rc-paper: #f5f2ea;
        --rc-sheet: #fffdf8;
        --rc-ink: #1b2236;
        --rc-ink-soft: #2c3550;
        --rc-text: #262a33;
        --rc-muted: #77736a;
        --rc-rule: #e3ddcf;
        --rc-rule-strong: #cfc7b5;
        --rc-cyan: #0f8fb3;
        --rc-magenta: #c8246c;
        --rc-yellow: #f2c200;
        --rc-key: #1b2236;
        --rc-mono: 'IBM Plex Mono', ui-monospace, monospace;
        --rc-sans: 'IBM Plex Sans', system-ui, sans-serif;
    }

    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) {
        background: var(--rc-paper) !important;
        color: var(--rc-text);
        font-family: var(--rc-sans);
        font-size: 13px;
    }

    /* ---------- Kontrol atas: tab & pengelompokan ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .seg-tab {
        min-height: 32px;
        padding: 6px 14px;
        background: var(--rc-sheet);
        color: var(--rc-muted);
        border-color: var(--rc-rule-strong);
        font-family: var(--rc-sans);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .01em;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .seg-tab:first-child { border-radius: 6px 0 0 6px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .seg-tab:last-child { border-radius: 0 6px 6px 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .seg-tab:hover { background: #fff; color: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .seg-tab.active {
        background: var(--rc-ink);
        border-color: var(--rc-ink);
        color: #fff;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar { border-left-color: var(--rc-rule-strong); gap: 0; }
    /* Tombol Per Order / By Divisi / By Produk menyatu jadi satu grup. */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar .seg-tab { border-right: 0; border-radius: 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar .group-toolbar-label + .seg-tab { border-radius: 6px 0 0 6px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar .seg-tab:last-child { border-right: 1px solid var(--rc-rule-strong); border-radius: 0 6px 6px 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar .seg-tab.active { border-color: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar .seg-tab.active + .seg-tab { border-left-color: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-toolbar-label {
        font-family: var(--rc-sans);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--rc-muted);
        margin-right: 10px;
    }

    /* ---------- Tombol & input ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-btn {
        font-family: var(--rc-sans);
        font-weight: 600;
        font-size: 12px;
        padding: 6px 12px;
        border-radius: 6px;
        background: var(--rc-ink);
        border-color: var(--rc-ink);
        color: #fff;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-btn:hover { background: var(--rc-ink-soft); border-color: var(--rc-ink-soft); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-btn:disabled:hover { background: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-btn-ghost { background: transparent; color: var(--rc-ink); border-color: var(--rc-rule-strong); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-btn-danger { background: var(--rc-magenta); border-color: var(--rc-magenta); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-input {
        min-height: 30px;
        border-radius: 6px;
        border-color: var(--rc-rule-strong);
        background: #fff;
        font-family: var(--rc-sans);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) input[name="qty"] {
        font-family: var(--rc-mono);
        font-weight: 600;
        text-align: right;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-input:focus {
        outline: none;
        border-color: var(--rc-cyan);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-cyan) 18%, transparent);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) input[type="checkbox"] { accent-color: var(--rc-ink); width: 15px; height: 15px; }

    /* ---------- Judul grup: strip CMYK seperti tanda register cetak ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading {
        position: relative;
        margin: 22px 0 8px;
        padding: 8px 14px 8px 22px;
        background: transparent;
        color: var(--rc-ink);
        border-left: 0;
        border-bottom: 2px solid var(--rc-ink);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading:first-child { margin-top: 2px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading::before {
        content: "";
        position: absolute;
        left: 0;
        top: 9px;
        bottom: 9px;
        width: 8px;
        background: linear-gradient(var(--rc-cyan) 0 25%, var(--rc-magenta) 25% 50%, var(--rc-yellow) 50% 75%, var(--rc-key) 75% 100%);
        border-radius: 1px;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading-title {
        font-family: var(--rc-sans);
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .01em;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading-count {
        font-family: var(--rc-mono);
        font-size: 11px;
        font-weight: 500;
        color: var(--rc-muted);
        opacity: 1;
    }

    /* ---------- Kartu order: lembar kertas ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-card {
        background: var(--rc-sheet) !important;
        border: 1px solid var(--rc-rule);
        border-left: 1px solid var(--rc-rule);
        border-radius: 8px;
        margin-bottom: 8px !important;
        box-shadow: 0 1px 0 var(--rc-rule), 0 6px 16px -10px rgba(27, 34, 54, .25);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail {
        background: var(--rc-ink);
        border-right: 0;
        color: #c9cfdf;
        font-family: var(--rc-mono);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail strong { color: #fff; font-family: var(--rc-mono); font-weight: 600; font-size: 20px; letter-spacing: -.02em; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail span,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail small { color: #aab2c8; font-family: var(--rc-sans); font-weight: 600; letter-spacing: .12em; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-card-head {
        background: transparent;
        border-bottom: 1px dashed var(--rc-rule-strong);
        padding: 7px 14px;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-identity {
        font-family: var(--rc-mono);
        font-weight: 600;
        font-size: 13px !important;
        color: var(--rc-ink);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-meta-customer {
        font-family: var(--rc-sans);
        font-weight: 600;
        color: var(--rc-text);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-meta-operator { font-family: var(--rc-sans); color: var(--rc-muted); font-weight: 500; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-meta-divider,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-meta-divider { background: var(--rc-rule-strong); }

    /* ---------- Baris item ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row {
        padding: 8px 14px;
        border-bottom: 1px solid var(--rc-rule);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row:hover { background: #faf6ec; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row strong { color: var(--rc-ink); font-weight: 600; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row > div:first-child span[style*="font-size: 14px"] {
        color: var(--rc-muted) !important;
        font-family: var(--rc-mono);
        font-size: 12px !important;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .progress-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px;
        border: 1px solid var(--rc-rule-strong);
        border-radius: 999px;
        background: #fff;
        color: var(--rc-muted);
        font-family: var(--rc-mono);
        font-size: 11px;
        font-weight: 500;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .progress-tag::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--rc-cyan);
    }

    /* ---------- Tag & status ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .tag-outline { border-color: var(--rc-magenta); color: var(--rc-magenta); background: #fff; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .blueprint { background: var(--rc-sheet); border: 1px dashed var(--rc-rule-strong); border-radius: 8px; color: var(--rc-muted); }

    /* ---------- Judul halaman (di luar area kerja) ---------- */
    body:has(.operator-page-title) .operator-page-title { color: #1b2236 !important; font-family: 'IBM Plex Sans', system-ui, sans-serif; }
    body:has(.operator-page-title) .operator-page-title::before {
        width: 6px;
        background: linear-gradient(#0f8fb3 0 25%, #c8246c 25% 50%, #f2c200 50% 75%, #1b2236 75% 100%);
        border-radius: 1px;
    }

    /* ---------- Mode rapat: baris lebih pendek agar lebih banyak data tampil ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-card { margin-bottom: 5px !important; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-card-head { min-height: 0; padding: 3px 12px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-identity { font-size: 12px !important; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row { padding: 2px 12px; min-height: 0; gap: 8px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row .in-input { height: 24px !important; min-height: 24px !important; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row .in-btn { min-height: 24px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row .progress-tag { min-height: 20px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row strong { font-size: 12.5px; line-height: 1.3; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .in-input { min-height: 24px; padding: 2px 6px; font-size: 12px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row .in-btn { padding: 3px 10px; font-size: 11.5px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .progress-tag { padding: 1px 7px; font-size: 10.5px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail strong { font-size: 17px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail span,
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-date-rail small { margin-top: 1px; font-size: 8.5px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .group-heading { margin: 14px 0 6px; padding: 5px 12px 5px 22px; }

    /* ---------- Tab stop: nama produk & No. Order lebar tetap agar kolom sejajar ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row > div:first-child { display: flex; align-items: center; min-width: 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row > div:first-child > strong {
        flex: 0 0 170px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-identity > span:first-child { min-width: 168px; }
    /* Tanda macet (!) dipindah ke kanan nama customer. */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-identity > span[title^="Macet"] { order: 99; width: 17px !important; height: 17px !important; font-size: 11px !important; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .item-row > div:first-child > :not(:first-child):not(.item-meta-divider) { margin-left: 5px; }


    /* ---------- Alias untuk komponen antrean bersama (Cetak s/d Pengambilan) ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar { border-left-color: var(--rc-rule-strong); gap: 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar-label { font-family: var(--rc-sans); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--rc-muted); margin-right: 10px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar .seg-tab { border-right: 0; border-radius: 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar .operator-group-toolbar-label + .seg-tab { border-radius: 6px 0 0 6px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar .seg-tab:last-child { border-right: 1px solid var(--rc-rule-strong); border-radius: 0 6px 6px 0; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar .seg-tab.active { border-color: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-toolbar .seg-tab.active + .seg-tab { border-left-color: var(--rc-ink); }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-heading {
        position: relative; margin: 14px 0 6px; padding: 5px 12px 5px 22px;
        background: transparent; color: var(--rc-ink); border-left: 0; border-bottom: 2px solid var(--rc-ink);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-heading:first-child { margin-top: 2px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-heading::before {
        content: ""; position: absolute; left: 0; top: 7px; bottom: 7px; width: 8px; border-radius: 1px;
        background: linear-gradient(var(--rc-cyan) 0 25%, var(--rc-magenta) 25% 50%, var(--rc-yellow) 50% 75%, var(--rc-key) 75% 100%);
    }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-heading-title { font-family: var(--rc-sans); font-size: 15px; font-weight: 700; letter-spacing: .01em; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-group-heading-count { font-family: var(--rc-mono); font-size: 11px; font-weight: 500; color: var(--rc-muted); opacity: 1; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-order-list::-webkit-scrollbar { width: 8px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .operator-order-list::-webkit-scrollbar-thumb { background: var(--rc-rule-strong); border-radius: 8px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .tag:not(.tag-outline) { border-radius: 999px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .tag-outline { border-radius: 999px; font-family: var(--rc-sans); font-size: 11px; }

    /* ---------- Scrollbar ---------- */
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-list-scroll::-webkit-scrollbar { width: 8px; }
    :is(#industry-desain, #industry-cetak, #industry-finishing, #industry-qc, #industry-bungkus, #industry-pengambilan):not(.tanpa-tema) .order-list-scroll::-webkit-scrollbar-thumb { background: var(--rc-rule-strong); border-radius: 8px; }
</style>
