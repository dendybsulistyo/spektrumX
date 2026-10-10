{{--
    Tema "Ruang Cetak" untuk seluruh aplikasi (menu, judul halaman, tombol,
    input, tabel). Pasangan dari <x-tema-ruang-cetak /> di antrean operator.
    Untuk membatalkan: hapus baris <x-tema-ruang-cetak-global /> di
    resources/views/layouts/app.blade.php.
--}}
<style>
    html body.font-sans {
        --rc-paper: #f5f2ea;
        --rc-sheet: #fffdf8;
        --rc-ink: #1b2236;
        --rc-ink-soft: #2c3550;
        --rc-ink-tint: #e9eaf0;
        --rc-text: #262a33;
        --rc-muted: #77736a;
        --rc-rule: #e3ddcf;
        --rc-rule-strong: #cfc7b5;
        --rc-cyan: #0f8fb3;
        --rc-cyan-dark: #0b6a86;
        --rc-magenta: #c8246c;
        --rc-yellow: #f2c200;
        --rc-mono: 'IBM Plex Mono', ui-monospace, monospace;
        --rc-sans: 'IBM Plex Sans', system-ui, sans-serif;
        font-family: var(--rc-sans) !important;
        background: var(--rc-paper) !important;
        color: var(--rc-text);
    }
    html body.font-sans h1, html body.font-sans h2, html body.font-sans h3, html body.font-sans h4 {
        font-family: var(--rc-sans) !important;
        color: var(--rc-ink);
    }

    /* Judul di dalam kotak berlatar gelap tetap mengikuti warna kotaknya (putih). */
    html body.font-sans :is(.bg-slate-900, .bg-slate-800, .bg-gray-900, .bg-gray-800, .bg-indigo-600, .bg-blue-600) :is(h1, h2, h3, h4) { color: inherit !important; }

    /* ---------- Menu atas ---------- */
    html body.font-sans .industry-nav {
        background: var(--rc-sheet) !important;
        border-bottom: 1px solid var(--rc-rule) !important;
        box-shadow: 0 1px 0 var(--rc-rule), 0 6px 18px -14px rgba(27, 34, 54, .35) !important;
    }
    html body.font-sans .industry-nav a, html body.font-sans .industry-nav button,
    html body.font-sans .industry-nav span, html body.font-sans .industry-nav div,
    html body.font-sans .industry-nav input { font-family: var(--rc-sans) !important; }
    html body.font-sans .industry-nav .brand-mark,
    html body.font-sans .industry-nav .brand-mark.bg-indigo-600 {
        position: relative;
        background: var(--rc-ink) !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        overflow: hidden;
    }
    html body.font-sans .industry-nav .brand-mark::after {
        content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 4px;
        background: linear-gradient(90deg, var(--rc-cyan) 0 25%, var(--rc-magenta) 25% 50%, var(--rc-yellow) 50% 75%, #fff 75% 100%);
    }
    html body.font-sans .industry-nav .nav-top-link { color: #5d5a52 !important; }
    html body.font-sans .industry-nav .nav-top-link:hover { background: #f1ece0 !important; color: var(--rc-ink) !important; }
    html body.font-sans .industry-nav .nav-top-link.bg-indigo-50 { background: var(--rc-ink) !important; color: #fff !important; box-shadow: none !important; }
    html body.font-sans .industry-nav .nav-dropdown-link:hover { background: #f4efe3 !important; color: var(--rc-ink) !important; }
    html body.font-sans .industry-nav .nav-dropdown-link.bg-indigo-50 { background: var(--rc-ink-tint) !important; color: var(--rc-ink) !important; box-shadow: inset 3px 0 0 var(--rc-cyan); }
    /* Menu yang disorot jadi tebal. Menu atas pakai tebal "semu" (text-shadow) agar
       lebar tulisan tidak berubah dan menu di sebelahnya tidak ikut bergeser. */
    html body.font-sans .industry-nav .nav-top-link:hover,
    html body.font-sans .industry-nav .nav-top-link:focus-visible {
        text-shadow: 0 0 .55px currentColor, 0 0 .55px currentColor;
    }
    html body.font-sans .industry-nav .nav-dropdown-link:hover,
    html body.font-sans .industry-nav .nav-dropdown-link:focus-visible,
    html body.font-sans .industry-nav a.block:hover { font-weight: 700 !important; }
    html body.font-sans .industry-nav .bg-white { background: var(--rc-sheet) !important; }
    html body.font-sans .industry-nav .border-gray-200 { border-color: var(--rc-rule) !important; }
    html body.font-sans .industry-nav p.bg-slate-100 {
        background: transparent !important;
        border: 0 !important;
        border-bottom: 1.5px solid var(--rc-ink) !important;
        border-radius: 0 !important;
        color: var(--rc-ink) !important;
        padding-left: 4px !important;
    }
    html body.font-sans .industry-nav .border-gray-100 { border-color: var(--rc-rule) !important; }

    /* Avatar mini di menu atas. */
    html body.font-sans .nav-avatar img { width: 100%; height: 100%; display: block; }
    html body.font-sans .nav-avatar:has(img) { background: #fff; box-shadow: 0 0 0 1.5px #e3ddcf; }

    /* ---------- Judul halaman ---------- */
    html body.font-sans > div > header.bg-white,
    html body.font-sans header.bg-white.border-b {
        background: var(--rc-sheet) !important;
        border-bottom: 1px solid var(--rc-rule) !important;
    }
    html body.font-sans header h2 { position: relative; padding-left: 16px; }
    html body.font-sans header h2::before {
        content: ""; position: absolute; left: 0; top: 50%; width: 6px; height: 19px; transform: translateY(-50%);
        background: linear-gradient(var(--rc-cyan) 0 25%, var(--rc-magenta) 25% 50%, var(--rc-yellow) 50% 75%, var(--rc-ink) 75% 100%);
        border-radius: 1px;
    }

    /* ---------- Permukaan ---------- */
    html body.font-sans .bg-gray-50, html body.font-sans .bg-slate-50 { background: #faf7f0 !important; }
    html body.font-sans .bg-gray-100, html body.font-sans .bg-slate-100 { background: #f1ece0 !important; }
    html body.font-sans main .bg-white { background: var(--rc-sheet) !important; }
    html body.font-sans main .shadow-sm, html body.font-sans main .shadow {
        box-shadow: 0 1px 0 var(--rc-rule), 0 6px 16px -12px rgba(27, 34, 54, .25) !important;
    }
    html body.font-sans .border-gray-100, html body.font-sans .border-gray-200,
    html body.font-sans .border-slate-100, html body.font-sans .border-slate-200 { border-color: var(--rc-rule) !important; }
    html body.font-sans .border-gray-300, html body.font-sans .border-slate-300 { border-color: var(--rc-rule-strong) !important; }
    html body.font-sans .divide-gray-100 > * + *, html body.font-sans .divide-gray-200 > * + *,
    html body.font-sans .divide-slate-100 > * + *, html body.font-sans .divide-y > * + * { border-color: var(--rc-rule) !important; }

    /* ---------- Teks ---------- */
    html body.font-sans .text-gray-900, html body.font-sans .text-gray-800,
    html body.font-sans .text-slate-900, html body.font-sans .text-slate-800 { color: var(--rc-ink) !important; }
    html body.font-sans .text-gray-500, html body.font-sans .text-gray-400,
    html body.font-sans .text-slate-500, html body.font-sans .text-slate-400 { color: var(--rc-muted) !important; }
    html body.font-sans main .text-indigo-600, html body.font-sans main .text-indigo-700,
    html body.font-sans main .text-blue-600, html body.font-sans main .text-blue-700 { color: var(--rc-cyan-dark) !important; }

    /* ---------- Tombol utama: tinta navy ---------- */
    html body.font-sans .bg-indigo-600, html body.font-sans .bg-indigo-500,
    html body.font-sans .bg-blue-600, html body.font-sans .bg-slate-800, html body.font-sans .bg-slate-700,
    html body.font-sans .bg-gray-800, html body.font-sans .bg-gray-900 { background: var(--rc-ink) !important; }
    html body.font-sans .hover\:bg-indigo-700:hover, html body.font-sans .hover\:bg-blue-700:hover,
    html body.font-sans .hover\:bg-slate-800:hover, html body.font-sans .hover\:bg-slate-900:hover,
    html body.font-sans .hover\:bg-gray-700:hover { background: var(--rc-ink-soft) !important; }
    html body.font-sans main .bg-indigo-50, html body.font-sans main .bg-indigo-100,
    html body.font-sans main .bg-blue-50 { background: var(--rc-ink-tint) !important; }

    /* ---------- Input ---------- */
    html body.font-sans main input:not([type="checkbox"]):not([type="radio"]),
    html body.font-sans main select, html body.font-sans main textarea {
        border-color: var(--rc-rule-strong) !important;
        background-color: #fff;
    }
    html body.font-sans main input:focus, html body.font-sans main select:focus, html body.font-sans main textarea:focus {
        border-color: var(--rc-cyan) !important;
        --tw-ring-color: color-mix(in srgb, var(--rc-cyan) 22%, transparent) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-cyan) 18%, transparent) !important;
    }
    html body.font-sans input[type="checkbox"], html body.font-sans input[type="radio"] { accent-color: var(--rc-ink); }

    /* ---------- Tabel ---------- */
    html body.font-sans main table { font-variant-numeric: tabular-nums; }
    html body.font-sans main thead { background: #f1ece0; }
    html body.font-sans main thead th { color: #5d5a52; letter-spacing: .04em; }
    html body.font-sans main tbody tr:nth-child(n):hover { background-color: #faf6ec !important; }

    /* Tabel laporan cetak (garis penuh): judul kolom krem & garis cokelat-abu hangat. */
    html body.font-sans main table th { background-color: #efe9db !important; color: var(--rc-ink) !important; }
    html body.font-sans main table th, html body.font-sans main table td { border-color: #bdb39c !important; }
    html body.font-sans main table tfoot td { background-color: #faf7f0; }
    html body.font-sans main section h1 { color: var(--rc-ink); letter-spacing: .02em; }

    /* ---------- Notifikasi ---------- */
    html body.font-sans main .bg-green-50 { background: #eef6ee !important; }
</style>
