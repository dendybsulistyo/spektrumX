{{--
    Dashboard tema "Ruang Cetak". Data & fungsi sama dengan dashboard.blade.php.
    Untuk kembali ke dashboard lama: di DashboardController@index ganti
    view('dashboard-ruang-cetak', ...) menjadi view('dashboard', ...).
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="rcd-header">
            <div>
                <h2>Dashboard</h2>
            </div>
            <form method="GET" class="rcd-filter">
                <div class="rcd-presets">
                    @php
                        $presets = [
                            'Hari ini' => [now()->toDateString(), now()->toDateString()],
                            '7 hari' => [now()->subDays(6)->toDateString(), now()->toDateString()],
                            'Bulan ini' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                        ];
                    @endphp
                    @foreach ($presets as $label => [$pFrom, $pTo])
                        <a href="{{ route('dashboard', ['from' => $pFrom, 'to' => $pTo]) }}"
                           @class(['rcd-preset', 'is-active' => $from === $pFrom && $to === $pTo])>{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('dashboard') }}" @class(['rcd-preset', 'is-active' => ! $from && ! $to])>Semua</a>
                </div>
                <label>Dari<input type="date" name="from" value="{{ $from }}"></label>
                <label>Sampai<input type="date" name="to" value="{{ $to }}"></label>
                <button type="submit">Terapkan</button>
            </form>
        </div>
    </x-slot>

    @push('styles')
        <style>
            #rc-dashboard {
                --ink: #1b2236; --ink-soft: #2c3550; --sheet: #fffdf8; --paper: #f5f2ea;
                --rule: #e3ddcf; --rule-strong: #cfc7b5; --muted: #77736a; --text: #262a33;
                --cyan: #0f8fb3; --magenta: #c8246c; --yellow: #f2c200; --green: #2e8b57;
                --mono: 'IBM Plex Mono', ui-monospace, monospace; --sans: 'IBM Plex Sans', system-ui, sans-serif;
                max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px;
                font-family: var(--sans); color: var(--text);
            }
            #rc-dashboard .mono { font-family: var(--mono); font-variant-numeric: tabular-nums; }
            #rc-dashboard .rcd-section-title {
                display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
                margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid var(--ink);
            }
            #rc-dashboard .rcd-section-title h3 {
                position: relative; margin: 0; padding-left: 16px; font-size: 14px; font-weight: 700;
                letter-spacing: .06em; text-transform: uppercase; color: var(--ink);
            }
            #rc-dashboard .rcd-section-title h3::before {
                content: ""; position: absolute; left: 0; top: 2px; bottom: 2px; width: 6px; border-radius: 1px;
                background: linear-gradient(var(--cyan) 0 25%, var(--magenta) 25% 50%, var(--yellow) 50% 75%, var(--ink) 75% 100%);
            }
            #rc-dashboard .rcd-section-title span { font-size: 12px; color: var(--muted); }
            #rc-dashboard .sheet {
                background: var(--sheet); border: 1px solid var(--rule); border-radius: 10px;
                box-shadow: 0 1px 0 var(--rule), 0 8px 20px -16px rgba(27, 34, 54, .35);
            }

            /* ---------- KPI ---------- */
            #rc-dashboard .rcd-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) minmax(0, 1.15fr); gap: 0; overflow: hidden; }
            #rc-dashboard .rcd-kpi { padding: 14px 18px 16px; border-left: 1px dashed var(--rule-strong); min-width: 0; }
            #rc-dashboard .rcd-kpi:first-child { border-left: 0; }
            #rc-dashboard .rcd-kpi-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
            #rc-dashboard .rcd-kpi-value { margin-top: 6px; font-family: var(--mono); font-size: 34px; font-weight: 600; line-height: 1; color: var(--ink); letter-spacing: -.02em; }
            #rc-dashboard .rcd-kpi-meta { margin-top: 8px; font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            #rc-dashboard .rcd-kpi-meta .mono { color: var(--ink); font-weight: 600; }
            #rc-dashboard .rcd-kpi-bar { margin-top: 10px; height: 4px; border-radius: 2px; background: #efe9db; overflow: hidden; }
            #rc-dashboard .rcd-kpi-bar > i { display: block; height: 100%; background: var(--cyan); }
            #rc-dashboard .rcd-kpi.alert { background: var(--ink); color: #fff; border-left: 0; position: relative; }
            #rc-dashboard .rcd-kpi.alert::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: var(--magenta); }
            #rc-dashboard .rcd-kpi.alert .rcd-kpi-label { color: #f3a9c9; }
            #rc-dashboard .rcd-kpi.alert .rcd-kpi-value { color: #fff; }
            #rc-dashboard .rcd-kpi.alert .rcd-kpi-meta { color: #c4c9d8; }
            #rc-dashboard .rcd-kpi.alert.calm::before { background: var(--green); }
            #rc-dashboard .rcd-kpi.alert.calm .rcd-kpi-label { color: #a7dcbc; }

            /* ---------- Alur produksi ---------- */
            #rc-dashboard .rcd-flow { padding: 14px 16px 16px; }
            #rc-dashboard .rcd-stages { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0; }
            #rc-dashboard .rcd-stage {
                position: relative; display: block; padding: 10px 14px 10px 18px; color: inherit; text-decoration: none;
                border: 1px solid var(--rule); border-left: 0; background: #fff; transition: background .15s ease;
            }
            #rc-dashboard .rcd-stage:first-child { border-left: 1px solid var(--rule); border-radius: 8px 0 0 8px; padding-left: 14px; }
            #rc-dashboard .rcd-stage:last-child { border-radius: 0 8px 8px 0; }
            #rc-dashboard a.rcd-stage:hover { background: #faf6ec; }
            #rc-dashboard .rcd-stage:not(:last-child)::after {
                content: ""; position: absolute; right: -7px; top: 50%; z-index: 1; width: 12px; height: 12px;
                background: #fff; border-top: 1px solid var(--rule-strong); border-right: 1px solid var(--rule-strong);
                transform: translateY(-50%) rotate(45deg);
            }
            #rc-dashboard a.rcd-stage:hover::after { background: #faf6ec; }
            #rc-dashboard .rcd-stage-top { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; }
            #rc-dashboard .rcd-stage-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
            #rc-dashboard .rcd-stage-num { font-family: var(--mono); font-size: 10px; color: var(--rule-strong); }
            #rc-dashboard .rcd-stage-value { margin-top: 4px; font-family: var(--mono); font-size: 28px; font-weight: 600; line-height: 1.05; color: var(--ink); }
            #rc-dashboard .rcd-stage-value.is-zero { color: var(--rule-strong); }
            #rc-dashboard .rcd-stage-dot { display: inline-block; width: 8px; height: 8px; margin-right: 6px; border-radius: 50%; vertical-align: 1px; }
            #rc-dashboard .rcd-stage.is-done { background: #f7f4ec; }
            #rc-dashboard .rcd-stage.is-done .rcd-stage-value { color: var(--green); }
            #rc-dashboard .rcd-distribution { display: flex; height: 8px; margin-top: 14px; border-radius: 4px; overflow: hidden; background: #efe9db; }
            #rc-dashboard .rcd-distribution > i { display: block; height: 100%; }
            #rc-dashboard .rcd-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 8px; font-size: 11.5px; color: var(--muted); }
            #rc-dashboard .rcd-legend b { font-family: var(--mono); font-weight: 600; color: var(--ink); }

            /* ---------- Monitoring ---------- */
            #rc-dashboard .rcd-monitor { padding: 0; overflow: hidden; }
            #rc-dashboard .rcd-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 16px; border-bottom: 1px dashed var(--rule-strong); }
            #rc-dashboard .rcd-seg { display: inline-flex; }
            #rc-dashboard .rcd-seg button {
                padding: 6px 14px; font-size: 12px; font-weight: 600; color: var(--muted); background: #fff;
                border: 1px solid var(--rule-strong); border-right: 0; cursor: pointer;
            }
            #rc-dashboard .rcd-seg button:first-child { border-radius: 6px 0 0 6px; }
            #rc-dashboard .rcd-seg button:last-child { border-right: 1px solid var(--rule-strong); border-radius: 0 6px 6px 0; }
            #rc-dashboard .rcd-seg button.is-active { background: var(--ink); border-color: var(--ink); color: #fff; }
            #rc-dashboard .rcd-search { position: relative; }
            #rc-dashboard .rcd-search input { width: 280px; height: 32px; padding: 0 10px 0 30px; font-size: 12.5px; border-radius: 6px; }
            #rc-dashboard .rcd-search svg { position: absolute; left: 9px; top: 50%; width: 14px; height: 14px; transform: translateY(-50%); color: var(--muted); }
            #rc-dashboard .rcd-table-wrap { overflow-x: auto; max-height: 62vh; overflow-y: auto; }
            #rc-dashboard table.rcd-table { width: 100%; min-width: 1240px; border-collapse: separate; border-spacing: 0; font-size: 12.5px; }
            #rc-dashboard .rcd-table thead th {
                position: sticky; top: 0; z-index: 2; padding: 8px 10px; text-align: left; white-space: nowrap;
                font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
                color: #5d5a52 !important; background: #f1ece0 !important; border-bottom: 1px solid var(--rule-strong) !important;
            }
            #rc-dashboard .rcd-table thead th.stage-col { text-align: center; }
            #rc-dashboard .rcd-table tbody td { padding: 6px 10px; border-bottom: 1px solid var(--rule) !important; vertical-align: middle; }
            #rc-dashboard .rcd-table tbody tr:hover td { background: #faf6ec; }
            #rc-dashboard .rcd-order { padding: 0; border: 0; background: none; font: inherit; font-family: var(--mono); font-weight: 600; color: var(--ink); cursor: pointer; border-bottom: 1px dashed var(--rule-strong); }
            #rc-dashboard .rcd-order:hover { color: var(--cyan); border-bottom-color: var(--cyan); }
            #rc-dashboard .rcd-tipe { display: inline-block; margin-left: 6px; padding: 0 5px; font-size: 9.5px; font-weight: 700; letter-spacing: .06em; border-radius: 3px; color: var(--muted); border: 1px solid var(--rule-strong); vertical-align: 1px; }
            #rc-dashboard .rcd-customer { font-weight: 600; color: var(--ink); }
            #rc-dashboard .rcd-date { font-family: var(--mono); font-size: 10.5px; color: var(--muted); white-space: nowrap; }
            #rc-dashboard .pill {
                display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px;
                font-size: 11px; font-weight: 600; white-space: nowrap; border: 1px solid transparent;
            }
            #rc-dashboard .pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
            #rc-dashboard .pill.no-dot::before { display: none; }
            #rc-dashboard .pill-late { background: #fbe6ef; color: var(--magenta); }
            #rc-dashboard .pill-count { background: #fff; color: var(--muted); border-color: var(--rule-strong); font-family: var(--mono); font-weight: 500; }
            #rc-dashboard .rcd-proses { font-family: var(--mono); font-size: 11.5px; color: var(--muted); text-align: right; white-space: nowrap; }
            #rc-dashboard .rcd-people { font-size: 12px; color: var(--muted); white-space: nowrap; }
            #rc-dashboard .rcd-cell-stage { text-align: center; white-space: nowrap; }
            #rc-dashboard .rcd-cell-stage .mono { font-size: 11.5px; font-weight: 600; color: var(--ink); }
            #rc-dashboard .rcd-cell-stage .mono.is-full { color: var(--green); }
            #rc-dashboard .rcd-cell-stage .mono.is-zero { color: var(--rule-strong); font-weight: 500; }
            #rc-dashboard .rcd-cell-stage small { display: block; margin-top: 1px; font-size: 10.5px; color: var(--muted); max-width: 90px; overflow: hidden; text-overflow: ellipsis; margin-inline: auto; }
            #rc-dashboard .rcd-meter { width: 56px; height: 3px; margin: 3px auto 0; border-radius: 2px; background: #efe9db; overflow: hidden; }
            #rc-dashboard .rcd-meter > i { display: block; height: 100%; background: var(--cyan); }
            #rc-dashboard .rcd-meter > i.is-full { background: var(--green); }
            #rc-dashboard .rcd-empty { padding: 28px; text-align: center; color: var(--muted); }

            /* ---------- Popup riwayat ---------- */
            #rc-dashboard .rcd-modal-backdrop { position: absolute; inset: 0; background: rgba(27, 34, 54, .55); }
            #rc-dashboard .rcd-modal { position: relative; width: 100%; max-width: 660px; max-height: 86vh; overflow-y: auto; padding: 0; }
            #rc-dashboard .rcd-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 14px 18px; background: var(--ink); color: #fff; border-radius: 10px 10px 0 0; }
            #rc-dashboard .rcd-modal-head h4 { margin: 0; font-family: var(--mono); font-size: 16px; color: #fff; }
            #rc-dashboard .rcd-modal-head div div { margin-top: 2px; font-size: 13px; color: #c4c9d8; }
            #rc-dashboard .rcd-modal-head button { height: 30px; padding: 0 12px; font-size: 12px; font-weight: 600; color: var(--ink); background: #fff; border: 0; border-radius: 6px; cursor: pointer; }
            #rc-dashboard .rcd-modal-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 18px; }
            #rc-dashboard .rcd-label { margin-bottom: 8px; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
            #rc-dashboard .rcd-item { padding: 8px 10px; margin-bottom: 6px; border: 1px solid var(--rule); border-radius: 8px; background: #fff; }
            #rc-dashboard .rcd-item-name { font-weight: 600; color: var(--ink); margin-bottom: 4px; }
            #rc-dashboard .rcd-item-name span { font-weight: 400; color: var(--muted); }

            @media (max-width: 1100px) {
                #rc-dashboard .rcd-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                #rc-dashboard .rcd-kpi:nth-child(4) { border-left: 0; }
                #rc-dashboard .rcd-kpi { border-top: 1px dashed var(--rule-strong); }
                #rc-dashboard .rcd-stages { grid-template-columns: repeat(4, minmax(0, 1fr)); row-gap: 8px; }
                #rc-dashboard .rcd-stage::after { display: none; }
                #rc-dashboard .rcd-stage { border-left: 1px solid var(--rule); border-radius: 8px !important; margin-right: 6px; }
            }

            /* ---------- Header halaman ---------- */
            .rcd-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; }
            .rcd-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #0b6a86; margin-bottom: 2px; }
            .rcd-header h2 { margin: 0; line-height: 1.2; font-size: 22px !important; font-weight: 700 !important; color: #1b2236 !important; }
            .rcd-subtitle { margin: 2px 0 0; font-size: 12.5px; color: #77736a; }
            .rcd-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
            .rcd-filter label { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: #77736a; }
            .rcd-filter input { height: 32px; width: 150px; padding: 0 8px; font-size: 12px; border: 1px solid #cfc7b5; border-radius: 6px; background: #fff; }
            .rcd-filter button { height: 32px; padding: 0 14px; font-size: 12px; font-weight: 600; color: #fff; background: #1b2236; border: 0; border-radius: 6px; cursor: pointer; }
            .rcd-filter button:hover { background: #2c3550; }
            .rcd-presets { display: inline-flex; margin-right: 6px; }
            .rcd-preset { display: inline-flex; align-items: center; height: 32px; padding: 0 11px; font-size: 12px; font-weight: 600; color: #77736a; background: #fff; border: 1px solid #cfc7b5; border-right: 0; text-decoration: none; }
            .rcd-preset:first-child { border-radius: 6px 0 0 6px; }
            .rcd-preset:last-child { border-right: 1px solid #cfc7b5; border-radius: 0 6px 6px 0; }
            .rcd-preset:hover { color: #1b2236; }
            .rcd-preset.is-active { background: #1b2236; border-color: #1b2236; color: #fff; }
        </style>
    @endpush

    @php
        $stageColors = [
            'baru' => '#9a948a', 'desain' => '#0f8fb3', 'cetak' => '#1b2236', 'finishing' => '#e0a800',
            'qc' => '#7a5cc4', 'bungkus' => '#c8246c', 'siap_diambil' => '#16857a', 'selesai' => '#2e8b57', 'batal' => '#9b1c1c',
        ];
        $statusLabel = fn (?string $status) => match ($status) {
            'baru' => 'Baru', 'desain' => 'Desain', 'cetak' => 'Cetak', 'finishing' => 'Finishing',
            'qc' => 'Back Office', 'bungkus' => 'Bungkus', 'siap_diambil' => 'Siap Diambil',
            'selesai' => 'Selesai', 'batal' => 'Batal', default => ucfirst($status ?? '-'),
        };
        $bayarStyle = fn (?string $statusBayar) => match ($statusBayar) {
            'lunas' => ['Lunas', '#e6f2ea', '#2e7a4f'],
            'dp' => ['DP', '#e2f1f7', '#0b6a86'],
            'hutang' => ['Hutang', '#fbe6ef', '#a3124a'],
            'belum_bayar' => ['Belum Bayar', '#fdf2d3', '#8a6400'],
            default => ['-', '#f1ece0', '#77736a'],
        };
        $total = max(1, (int) $stats['total']);
        $percent = fn (float $ratio) => $ratio > 0 && $ratio < .01 ? '<1%' : number_format($ratio * 100, 0).'%';
        $kpis = [
            ['label' => 'Order masuk', 'value' => $stats['total'], 'meta' => 'Semua tipe order', 'ratio' => null],
            ['label' => 'Menunggu bayar', 'value' => $stats['belum_bayar'], 'meta' => 'Belum ada pembayaran', 'ratio' => $stats['belum_bayar'] / $total],
            ['label' => 'Lunas', 'value' => $stats['lunas'], 'meta' => 'Pembayaran diterima', 'ratio' => $stats['lunas'] / $total],
            ['label' => 'VIP / Hutang', 'value' => $stats['hutang'], 'money' => $stats['hutang_nominal'], 'ratio' => $stats['hutang'] / $total],
            ['label' => 'DP / Piutang berjalan', 'value' => $stats['dp'], 'money' => $stats['dp_nominal'], 'ratio' => $stats['dp'] / $total],
        ];
        $stages = [
            ['key' => 'desain', 'num' => '01', 'label' => 'Desain', 'route' => 'order-desain.index'],
            ['key' => 'cetak', 'num' => '02', 'label' => 'Cetak', 'route' => 'order-cetak.index'],
            ['key' => 'finishing', 'num' => '03', 'label' => 'Finishing', 'route' => 'order-finishing.index'],
            ['key' => 'qc', 'num' => '04', 'label' => 'Back Office', 'route' => 'order-qc.index'],
            ['key' => 'bungkus', 'num' => '05', 'label' => 'Bungkus', 'route' => 'order-bungkus.index'],
            ['key' => 'siap_diambil', 'num' => '06', 'label' => 'Siap Diambil', 'route' => 'pengambilan.index'],
            ['key' => 'selesai', 'num' => '07', 'label' => 'Selesai', 'route' => null],
        ];
        $inProgress = collect($stages)->reject(fn ($s) => $s['key'] === 'selesai')->sum(fn ($s) => (int) $stats[$s['key']]);
        $countsByTipe = $recent->countBy('tipe');
        $stageCols = [
            'desain' => 'Layout', 'cetak' => 'Cetak', 'finishing' => 'Finishing',
            'qc' => 'Back Office', 'bungkus' => 'Bungkus', 'pengambilan' => 'Ambil',
        ];
        $progressParts = function (?string $progress): array {
            if (! $progress || ! preg_match('/^(\d+)\s*\/\s*(\d+)$/', trim($progress), $m) || (int) $m[2] === 0) {
                return [null, null];
            }

            return [(int) $m[1], (int) $m[2]];
        };
    @endphp

    <div id="rc-dashboard"
         x-data="{
             tipeTab: 'semua',
             search: '',
             historyOpen: false,
             historyLoading: false,
             historyData: null,
             matches(tipe, text) {
                 const okTipe = this.tipeTab === 'semua' || this.tipeTab === tipe;
                 const q = this.search.trim().toLowerCase();
                 return okTipe && (q === '' || text.includes(q));
             },
             openHistory(type, id) {
                 this.historyOpen = true;
                 this.historyLoading = true;
                 this.historyData = null;
                 axios.get(`{{ url('/dashboard/order-progress') }}/${type}/${id}`)
                     .then(r => { this.historyData = r.data; })
                     .finally(() => { this.historyLoading = false; });
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
         }">

        {{-- ================= Ringkasan ================= --}}
        <section>
            <div class="rcd-section-title"><h3>Ringkasan order</h3><span>dari {{ number_format($stats['total'], 0, ',', '.') }} order</span></div>
            <div class="sheet rcd-kpis">
                @foreach ($kpis as $kpi)
                    <div class="rcd-kpi">
                        <div class="rcd-kpi-label">{{ $kpi['label'] }}</div>
                        <div class="rcd-kpi-value">{{ number_format($kpi['value'], 0, ',', '.') }}</div>
                        <div class="rcd-kpi-meta">
                            @isset($kpi['money'])
                                Rp <span class="mono">{{ number_format($kpi['money'], 0, ',', '.') }}</span>
                            @else
                                {{ $kpi['meta'] }}
                            @endisset
                            @if ($kpi['ratio'] !== null)
                                &middot; <span class="mono">{{ $percent($kpi['ratio']) }}</span>
                            @endif
                        </div>
                        @if ($kpi['ratio'] !== null)
                            <div class="rcd-kpi-bar"><i style="width: {{ max(2, min(100, $kpi['ratio'] * 100)) }}%"></i></div>
                        @endif
                    </div>
                @endforeach
                <div @class(['rcd-kpi alert', 'calm' => (int) $stats['telat'] === 0])>
                    <div class="rcd-kpi-label">Telat &gt; 3&times;24 jam</div>
                    <div class="rcd-kpi-value">{{ number_format($stats['telat'], 0, ',', '.') }}</div>
                    <div class="rcd-kpi-meta">{{ (int) $stats['telat'] === 0 ? 'Semua order sesuai jadwal' : 'Order belum siap diambil' }}</div>
                </div>
            </div>
        </section>

        {{-- ================= Alur produksi ================= --}}
        <section>
            <div class="rcd-section-title"><h3>Alur produksi</h3><span><span class="mono">{{ number_format($inProgress, 0, ',', '.') }}</span> order masih berjalan &middot; klik tahap untuk membuka antreannya</span></div>
            <div class="sheet rcd-flow">
                <div class="rcd-stages">
                    @foreach ($stages as $stage)
                        @php
                            $count = (int) $stats[$stage['key']];
                            $href = $stage['route'] && Route::has($stage['route']) ? route($stage['route']) : null;
                        @endphp
                        <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif
                            @class(['rcd-stage', 'is-done' => $stage['key'] === 'selesai'])>
                            <div class="rcd-stage-top">
                                <span class="rcd-stage-label"><span class="rcd-stage-dot" style="background: {{ $stageColors[$stage['key']] }}"></span>{{ $stage['label'] }}</span>
                                <span class="rcd-stage-num">{{ $stage['num'] }}</span>
                            </div>
                            <div @class(['rcd-stage-value', 'is-zero' => $count === 0])>{{ number_format($count, 0, ',', '.') }}</div>
                        </{{ $href ? 'a' : 'div' }}>
                    @endforeach
                </div>
                @if ($inProgress > 0)
                    <div class="rcd-distribution" title="Sebaran order yang masih berjalan">
                        @foreach ($stages as $stage)
                            @continue($stage['key'] === 'selesai' || (int) $stats[$stage['key']] === 0)
                            <i style="width: {{ $stats[$stage['key']] / $inProgress * 100 }}%; background: {{ $stageColors[$stage['key']] }}"
                               title="{{ $stage['label'] }}: {{ $stats[$stage['key']] }}"></i>
                        @endforeach
                    </div>
                    <div class="rcd-legend">
                        @foreach ($stages as $stage)
                            @continue($stage['key'] === 'selesai')
                            <span><span class="rcd-stage-dot" style="background: {{ $stageColors[$stage['key']] }}"></span>{{ $stage['label'] }} <b>{{ $percent($stats[$stage['key']] / $inProgress) }}</b></span>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ================= Monitoring ================= --}}
        <section>
            <div class="rcd-section-title">
                <h3>Monitoring order</h3>
                <span>
                    @if ($from || $to)
                        Order pada periode terpilih
                    @else
                        <span class="mono">{{ $recent->count() }}</span> order terbaru
                    @endif
                    &middot; kasir &rarr; layout &rarr; cetak &rarr; finishing &rarr; back office &rarr; bungkus &rarr; ambil
                </span>
            </div>
            <div class="sheet rcd-monitor">
                <div class="rcd-toolbar">
                    <div class="rcd-seg">
                        <button type="button" :class="tipeTab === 'semua' && 'is-active'" @click="tipeTab = 'semua'">Semua ({{ $recent->count() }})</button>
                        <button type="button" :class="tipeTab === 'indoor' && 'is-active'" @click="tipeTab = 'indoor'">Indoor ({{ $countsByTipe['Indoor'] ?? 0 }})</button>
                        <button type="button" :class="tipeTab === 'outdoor' && 'is-active'" @click="tipeTab = 'outdoor'">Outdoor ({{ $countsByTipe['Outdoor'] ?? 0 }})</button>
                    </div>
                    <label class="rcd-search">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="M13.2 13.2L17 17" stroke-linecap="round"/></svg>
                        <input type="search" x-model="search" placeholder="Cari no. order atau customer…">
                    </label>
                </div>
                <div class="rcd-table-wrap">
                    <table class="rcd-table">
                        <thead>
                            <tr>
                                <th style="width: 36px;">No</th>
                                <th>No. Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Bayar</th>
                                <th style="text-align: right;">Proses</th>
                                <th>Penerima</th>
                                <th>Kasir</th>
                                @foreach ($stageCols as $label)
                                    <th class="stage-col">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $row)
                                @php
                                    [$bayarText, $bayarBg, $bayarFg] = $bayarStyle($row['status_bayar']);
                                    $searchText = mb_strtolower(($row['no_order'] ?? '').' '.($row['customer'] ?? ''));
                                    $statusColor = $stageColors[$row['status']] ?? '#77736a';
                                @endphp
                                <tr x-show="matches('{{ strtolower($row['tipe']) }}', @js($searchText))">
                                    <td class="mono" style="color: var(--muted);">{{ $loop->iteration }}</td>
                                    <td style="white-space: nowrap;">
                                        <button type="button" class="rcd-order" @click="openHistory('{{ $row['type_slug'] }}', {{ $row['id'] }})"
                                                title="Lihat riwayat proses">{{ $row['no_order'] }}</button>
                                    </td>
                                    <td>
                                        <div class="rcd-customer">{{ $row['customer'] ? ucwords(mb_strtolower($row['customer'])) : '-' }}</div>
                                        <div class="rcd-date">{{ $row['created_at']?->format('d M Y · H:i') ?? '-' }}</div>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <span class="pill" style="background: color-mix(in srgb, {{ $statusColor }} 13%, #fff); color: {{ $statusColor }};">{{ $statusLabel($row['status']) }}</span>
                                        @if ($row['progress'])
                                            <span class="pill pill-count no-dot">{{ $row['progress'] }}</span>
                                        @endif
                                        @if ($row['telat'])
                                            <span class="pill pill-late">Telat</span>
                                        @endif
                                    </td>
                                    <td><span class="pill no-dot" style="background: {{ $bayarBg }}; color: {{ $bayarFg }};">{{ $bayarText }}</span></td>
                                    <td class="rcd-proses">{{ $row['durasi'] ?? '-' }}</td>
                                    <td class="rcd-people">{{ $row['operator_file'] ?? '-' }}</td>
                                    <td class="rcd-people">{{ $row['kasir'] ?? '-' }}</td>
                                    @foreach (array_keys($stageCols) as $key)
                                        @php
                                            [$done, $of] = $progressParts($row[$key.'_progress'] ?? null);
                                            $by = $row[$key.'_by'] ?? null;
                                        @endphp
                                        <td class="rcd-cell-stage">
                                            @if ($of)
                                                <span @class(['mono', 'is-full' => $done >= $of, 'is-zero' => $done === 0])>{{ $done }}/{{ $of }}</span>
                                                <div class="rcd-meter"><i @class(['is-full' => $done >= $of]) style="width: {{ min(100, $done / $of * 100) }}%"></i></div>
                                            @elseif (! empty($row[$key.'_progress']))
                                                <span class="mono">{{ $row[$key.'_progress'] }}</span>
                                            @else
                                                <span class="mono is-zero">–</span>
                                            @endif
                                            @if ($by && $by !== '-')
                                                <small title="{{ $by }}">{{ $by }}</small>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="14" class="rcd-empty">Tidak ada order pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ================= Popup riwayat proses ================= --}}
        <div x-show="historyOpen" x-cloak @keydown.escape.window="historyOpen = false"
             style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div class="rcd-modal-backdrop" @click="historyOpen = false"></div>
            <div class="sheet rcd-modal">
                <div class="rcd-modal-head">
                    <div>
                        <h4 x-text="historyData ? historyData.no_order : 'Memuat…'"></h4>
                        <div x-text="historyData ? (historyData.customer || '-') : ''"></div>
                    </div>
                    <button type="button" @click="historyOpen = false">Tutup</button>
                </div>
                <div class="rcd-modal-body">
                    <template x-if="historyLoading">
                        <div class="rcd-empty">Memuat riwayat…</div>
                    </template>
                    <template x-if="!historyLoading && historyData">
                        <div style="display: flex; flex-direction: column; gap: 18px;">
                            <div>
                                <div class="rcd-label">Item &amp; posisi qty saat ini</div>
                                <template x-for="item in historyData.items" :key="item.id">
                                    <div class="rcd-item">
                                        <div class="rcd-item-name">
                                            <span style="color: var(--ink); font-weight: 600;" x-text="item.name"></span>
                                            <template x-if="item.file"><span x-text="' · ' + item.file"></span></template>
                                            <span class="mono" x-text="' · Qty ' + item.qty_total"></span>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            <template x-for="stage in item.stages.filter(s => s.qty > 0)" :key="stage.label">
                                                <span class="pill pill-count no-dot" x-text="stage.qty + ' di ' + stage.label"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div>
                                <div class="rcd-label">Riwayat proses (terbaru dulu)</div>
                                <div style="display: flex; flex-direction: column; max-height: 340px; overflow-y: auto; padding-right: 10px;">
                                    <template x-for="(h, idx) in historyData.history" :key="idx">
                                        <div style="display: flex; gap: 12px;">
                                            <div style="display: flex; flex-direction: column; align-items: center; width: 28px; flex-shrink: 0;">
                                                <div :style="`width:26px; height:26px; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1.5px solid ${historyIconFor(h).color}; background:color-mix(in srgb, ${historyIconFor(h).color} 12%, white); color:${historyIconFor(h).color};`">
                                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" x-html="historyIcons[historyIconFor(h).icon]"></svg>
                                                </div>
                                                <template x-if="idx < historyData.history.length - 1">
                                                    <div style="width: 2px; flex: 1; min-height: 26px; background: var(--rule);"></div>
                                                </template>
                                            </div>
                                            <div style="flex: 1; display: flex; justify-content: space-between; gap: 14px; padding-bottom: 16px; font-size: 13px;">
                                                <div>
                                                    <div>
                                                        <span style="font-weight: 600; color: var(--ink);" x-text="h.stage"></span>
                                                        <span style="color: var(--muted);"> &middot; </span>
                                                        <span x-text="(h.qty !== null ? h.qty + ' unit' : h.action)"></span>
                                                        <span style="color: var(--muted);" x-show="h.action === 'selesai'"> (tuntas di tahap ini)</span>
                                                    </div>
                                                    <template x-if="h.catatan">
                                                        <div style="margin-top: 2px; color: var(--muted);" x-text="h.catatan"></div>
                                                    </template>
                                                </div>
                                                <div style="white-space: nowrap; text-align: right; line-height: 1.5; color: var(--muted);">
                                                    <div class="mono" style="font-size: 11.5px;" x-text="h.created_at"></div>
                                                    <div style="margin-top: 2px;" x-text="h.user"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="historyData.history.length === 0">
                                        <div class="rcd-empty">Belum ada riwayat.</div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
