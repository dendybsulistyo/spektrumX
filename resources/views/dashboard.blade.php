<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Dashboard</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            /* Reskin lokal khusus Dashboard — override token "Industry" (yang
               dipakai halaman lain) supaya lebih modern: font Figtree (sudah
               self-hosted lewat fonts.css, tidak butuh internet), aksen indigo
               tegas menggantikan biru-abu pucat, radius kecil (max 4px)
               menggantikan sudut siku 0 + bingkai "blueprint" teknis, dan kartu
               solid putih+shadow menggantikan kartu transparan hairline-border.
               Halaman lain tidak tersentuh karena semua override di-scope ke
               #industry-dashboard. */
            #industry-dashboard {
                --font-heading: 'Figtree', system-ui, sans-serif;
                --font-heading-weight: 600;
                --font-body: 'Figtree', system-ui, sans-serif;
                --color-accent: #4f46e5;
                --color-accent-100: #eef2ff;
                --color-accent-600: #4338ca;
                --color-accent-700: #3730a3;
                --color-accent-800: #312e81;
                --color-accent-900: #27216b;
                --color-bg: #f4f5f8;
                --color-surface: #ffffff;
                --color-divider: #e4e6ee;
                --radius-md: 4px;
                --radius-lg: 4px;
                font-family: var(--font-body); color: var(--color-text); background: var(--color-bg);
                margin: calc(var(--space-8) * -1); padding: var(--space-8);
            }
            #industry-dashboard .card, #industry-dashboard .btn, #industry-dashboard .input,
            #industry-dashboard .tag, #industry-dashboard .seg, #industry-dashboard .dialog {
                border-radius: 4px;
            }
            #industry-dashboard .panel, #industry-dashboard .card {
                background: var(--color-surface); border: 1px solid var(--color-divider);
                box-shadow: var(--shadow-sm);
            }
            #industry-dashboard .panel { padding: var(--space-6); }
            #industry-dashboard .card-kicker { font-weight: 700; letter-spacing: 0.06em; }

            /* Badge status — isi solid + teks putih, tegas dan gampang dipindai
               sekilas, bukan chip pastel yang low-contrast. */
            #industry-dashboard .tag { font-weight: 600; border: none; }
            #industry-dashboard .tag-outline { border: 1.5px solid var(--color-accent); color: var(--color-accent); background: var(--color-accent-100); }
            #industry-dashboard .tag-neutral { background: #eef1f6; color: #384054; }
            #industry-dashboard .tag-danger { background: #dc2626; color: #fff; }
            #industry-dashboard .tag-success { background: #059669; color: #fff; }
            #industry-dashboard .tag-info { background: #2563eb; color: #fff; }
            #industry-dashboard .tag-red { background: #ea580c; color: #fff; }
            #industry-dashboard .tag-indigo { background: #4f46e5; color: #fff; }
            #industry-dashboard .tag-cyan { background: #0891b2; color: #fff; }
            #industry-dashboard .tag-amber { background: #d97706; color: #fff; }
            #industry-dashboard .tag-purple { background: #7c3aed; color: #fff; }
            #industry-dashboard .tag-pink { background: #db2777; color: #fff; }
            #industry-dashboard .tag-teal { background: #0d9488; color: #fff; }

            #industry-dashboard .btn-primary { background: var(--color-accent); border-color: var(--color-accent); box-shadow: var(--shadow-sm); }
            #industry-dashboard .btn-primary:hover { background: var(--color-accent-600); }
            #industry-dashboard .btn-secondary { background: #fff; border-color: var(--color-divider); }

            #industry-dashboard .seg { border-color: var(--color-divider); box-shadow: var(--shadow-sm); }
            #industry-dashboard .table th { background: var(--color-accent-100); color: var(--color-accent-800); font-weight: 700; }
            #industry-dashboard .table tbody tr:nth-child(even) { background: color-mix(in srgb, var(--color-text) 2.5%, transparent); }
            #industry-dashboard .table tbody tr:hover { background: var(--color-accent-100); }

            /* .text-muted global (55% opacity) kurang kontras — digelapkan
               khusus di sini saja, tidak menyentuh halaman lain. */
            #industry-dashboard .text-muted { color: color-mix(in srgb, var(--color-text) 80%, transparent); }
        </style>
    @endpush

    @php
        $tagFor = fn (?string $status) => match ($status) {
            'baru' => 'tag tag-outline',
            'desain' => 'tag tag-indigo',
            'cetak' => 'tag tag-cyan',
            'finishing' => 'tag tag-amber',
            'qc' => 'tag tag-purple',
            'bungkus' => 'tag tag-pink',
            'siap_diambil' => 'tag tag-teal',
            'selesai' => 'tag tag-success',
            'batal' => 'tag tag-danger',
            default => 'tag tag-neutral',
        };
        $bayarTagFor = fn (?string $statusBayar) => match ($statusBayar) {
            'lunas' => 'tag tag-success',
            'dp' => 'tag tag-info',
            'belum_bayar', 'hutang' => 'tag tag-red',
            default => 'tag tag-outline',
        };
        $statusLabel = fn (?string $status) => match ($status) {
            'baru' => 'Baru', 'desain' => 'Desain', 'cetak' => 'Cetak', 'finishing' => 'Finishing',
            'qc' => 'QC', 'bungkus' => 'Bungkus', 'siap_diambil' => 'Siap Diambil',
            'selesai' => 'Selesai', 'batal' => 'Batal', default => ucfirst($status ?? '-'),
        };
        $bayarLabel = fn (?string $statusBayar) => match ($statusBayar) {
            'lunas' => 'Lunas', 'belum_bayar' => 'Belum Bayar', 'hutang' => 'Hutang', 'dp' => 'DP', default => '-',
        };
        $cards = [
            ['label' => 'Order masuk', 'value' => $stats['total'], 'meta' => 'Semua tipe order'],
            ['label' => 'Menunggu bayar', 'value' => $stats['belum_bayar'], 'meta' => 'Belum lunas'],
            ['label' => 'Lunas', 'value' => $stats['lunas'], 'meta' => 'Pembayaran diterima'],
            ['label' => 'VIP / Hutang', 'value' => $stats['hutang'], 'meta' => 'Rp '.number_format($stats['hutang_nominal'], 0, ',', '.')],
            ['label' => 'DP / Piutang Berjalan', 'value' => $stats['dp'], 'meta' => 'Rp '.number_format($stats['dp_nominal'], 0, ',', '.')],
        ];
        $stages = [
            ['num' => '01', 'label' => 'Desain', 'count' => $stats['desain']],
            ['num' => '02', 'label' => 'Cetak', 'count' => $stats['cetak']],
            ['num' => '03', 'label' => 'Finishing', 'count' => $stats['finishing']],
            ['num' => '04', 'label' => 'QC', 'count' => $stats['qc']],
            ['num' => '05', 'label' => 'Bungkus', 'count' => $stats['bungkus']],
            ['num' => '06', 'label' => 'Siap Diambil', 'count' => $stats['siap_diambil']],
            ['num' => '07', 'label' => 'Selesai', 'count' => $stats['selesai']],
        ];
        $countsByTipe = $recent->countBy('tipe');
    @endphp

    <div id="industry-dashboard">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-8);">

            <header style="display: flex; align-items: flex-end; justify-content: space-between; gap: var(--space-6); flex-wrap: wrap;">
                <div>
                    <div class="card-kicker" style="margin-bottom: 4px;">Ringkasan operasional</div>
                    <h2 style="margin: 0;">Dashboard</h2>
                </div>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 160px;">
                        <label>Dari tanggal</label>
                        <input class="input" type="date" name="from" value="{{ $from }}">
                    </div>
                    <div class="field" style="width: 160px;">
                        <label>Sampai tanggal</label>
                        <input class="input" type="date" name="to" value="{{ $to }}">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 36px;">Terapkan</button>
                    @if ($from || $to)
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                    @endif
                </form>
            </header>

            <section style="display: grid; grid-template-columns: repeat(6, 1fr); gap: var(--space-4);">
                @foreach ($cards as $card)
                    <div class="card">
                        <div class="card-kicker">{{ $card['label'] }}</div>
                        <div style="font-family: var(--font-heading); font-weight: 700; font-size: 40px; line-height: 1.1;">{{ $card['value'] }}</div>
                        <div class="card-meta">{{ $card['meta'] }}</div>
                    </div>
                @endforeach
                <div class="card" style="background: #dc2626; color: #fff; border-color: #dc2626;">
                    <div class="card-kicker" style="color: #fecaca; font-weight: 700; font-size: 12px;">Telat &gt; 3&times;24 jam</div>
                    <div style="font-family: var(--font-heading); font-weight: 700; font-size: 40px; line-height: 1.1; color: #fff;">{{ $stats['telat'] }}</div>
                    <div class="card-meta" style="color: #fecaca;">Belum siap diambil</div>
                </div>
            </section>

            <section class="panel" style="display: grid; grid-template-columns: repeat(7, 1fr); padding: 0;">
                @foreach ($stages as $s)
                    <div style="padding: var(--space-3) var(--space-4); border-left: 1px solid var(--color-divider); display: flex; flex-direction: column; gap: 4px;">
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
                            <span class="card-kicker">{{ $s['label'] }}</span>
                            <span class="text-muted" style="font-size: 11px; letter-spacing: 0.08em;">{{ $s['num'] }}</span>
                        </div>
                        <div style="font-family: var(--font-heading); font-weight: 700; color: var(--color-accent); font-size: 32px; line-height: 1.1;">{{ $s['count'] }}</div>
                    </div>
                @endforeach
            </section>

            <section class="panel"
                     x-data="{
                         tipeTab: 'semua',
                         historyOpen: false,
                         historyLoading: false,
                         historyData: null,
                         openHistory(type, id) {
                             this.historyOpen = true;
                             this.historyLoading = true;
                             this.historyData = null;
                             axios.get(`{{ url('/dashboard/order-progress') }}/${type}/${id}`)
                                 .then(r => { this.historyData = r.data; })
                                 .finally(() => { this.historyLoading = false; });
                         },
                         // Ikon + warna per tahap/aksi untuk timeline riwayat proses.
                         // Aksi (diulang/dibatalkan/dst) menang atas ikon tahap biasa
                         // karena lebih penting disorot sekilas dibanding tahapnya.
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
                                 dibatalkan: { icon: 'x', color: '#dc2626' },
                                 ditolak: { icon: 'x', color: '#dc2626' },
                                 diulang: { icon: 'rotate', color: '#b45309' },
                                 disetujui: { icon: 'check', color: '#059669' },
                                 diajukan: { icon: 'clock', color: '#6b7280' },
                                 nota_pengganti: { icon: 'kasir', color: '#475569' },
                             };
                             const byStage = {
                                 kasir: { icon: 'kasir', color: '#475569' },
                                 desain: { icon: 'pencil', color: '#4f46e5' },
                                 cetak: { icon: 'printer', color: '#0891b2' },
                                 finishing: { icon: 'scissors', color: '#d97706' },
                                 qc: { icon: 'shield', color: '#7c3aed' },
                                 bungkus: { icon: 'box', color: '#db2777' },
                                 siap_diambil: { icon: 'checkcircle', color: '#0d9488' },
                                 selesai: { icon: 'bag', color: '#059669' },
                                 pembatalan: { icon: 'x', color: '#dc2626' },
                             };
                             return byAction[h.action] || byStage[h.stage_key] || { icon: 'dot', color: '#6b7280' };
                         },
                     }">
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap; margin-bottom: var(--space-4);">
                    <div>
                        <h4 style="margin: 0 0 2px;">Monitoring order terbaru</h4>
                        <div class="text-muted" style="font-size: 13px;">
                            @if ($from || $to)
                                Order dari {{ $from ?: 'awal' }} sampai {{ $to ?: 'sekarang' }} —
                            @else
                                {{ $recent->count() }} order terbaru —
                            @endif
                            alur: kasir &rarr; layout &rarr; cetak &rarr; finishing &rarr; QC &rarr; bungkus &rarr; pengambilan.
                        </div>
                    </div>
                    <span class="tag tag-outline">{{ $recent->count() }} order ditampilkan</span>
                </div>

                <div class="seg" style="margin-bottom: var(--space-4);">
                    <label class="seg-opt">
                        <input type="radio" name="tipeTab" value="semua" x-model="tipeTab">
                        Semua ({{ $recent->count() }})
                    </label>
                    <label class="seg-opt">
                        <input type="radio" name="tipeTab" value="indoor" x-model="tipeTab">
                        Indoor ({{ $countsByTipe['Indoor'] ?? 0 }})
                    </label>
                    <label class="seg-opt">
                        <input type="radio" name="tipeTab" value="outdoor" x-model="tipeTab">
                        Outdoor ({{ $countsByTipe['Outdoor'] ?? 0 }})
                    </label>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 1240px;">
                        <thead>
                            <tr>
                                <th style="width: 32px;">No</th><th>No order</th><th>Customer</th><th>Status</th><th>Bayar</th><th style="text-align: right;">Proses</th><th>Penerima</th><th>Kasir</th><th>Layout</th><th>Cetak</th><th>Finishing</th><th>BackOffice</th><th>Bungkus</th><th>Ambil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $row)
                                <tr x-show="tipeTab === 'semua' || tipeTab === '{{ strtolower($row['tipe']) }}'">
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600; letter-spacing: 0.03em;">
                                        <button type="button" @click="openHistory('{{ $row['type_slug'] }}', {{ $row['id'] }})"
                                                style="background: none; border: none; padding: 0; font: inherit; color: var(--color-accent); cursor: pointer; text-decoration: underline; text-underline-offset: 2px;">
                                            {{ $row['no_order'] }}
                                        </button>
                                    </td>
                                    <td>
                                        <div>{{ $row['customer'] ? ucwords(mb_strtolower($row['customer'])) : '-' }}</div>
                                        <div class="text-muted" style="font-size: 11px; white-space: nowrap;">{{ $row['created_at']?->format('d M Y H:i') ?? '-' }}</div>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <span class="{{ $tagFor($row['status']) }}">{{ $statusLabel($row['status']) }}</span>
                                        @if ($row['progress'])
                                            <span class="tag tag-outline" style="margin-left: 4px;">{{ $row['progress'] }}</span>
                                        @endif
                                        @if ($row['telat'])
                                            <span class="tag tag-red" style="margin-left: 4px;">Telat</span>
                                        @endif
                                    </td>
                                    <td><span class="{{ $bayarTagFor($row['status_bayar']) }}">{{ $bayarLabel($row['status_bayar']) }}</span></td>
                                    <td style="text-align: right; white-space: nowrap;">{{ $row['durasi'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['operator_file'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['kasir'] ?? '-' }}</td>
                                    <td class="text-muted">
                                        @if ($row['desain_progress'])<div>{{ $row['desain_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['desain_by'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-muted">
                                        @if ($row['cetak_progress'])<div>{{ $row['cetak_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['cetak_by'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-muted">
                                        @if ($row['finishing_progress'])<div>{{ $row['finishing_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['finishing_by'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-muted">
                                        @if ($row['qc_progress'])<div>{{ $row['qc_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['qc_by'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-muted">
                                        @if ($row['bungkus_progress'])<div>{{ $row['bungkus_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['bungkus_by'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-muted">
                                        @if ($row['pengambilan_progress'])<div>{{ $row['pengambilan_progress'] }}</div>@endif
                                        <div style="font-size: 11px;">{{ $row['pengambilan_by'] ?? '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="14" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="historyOpen" x-cloak @keydown.escape.window="historyOpen = false"
                     style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: var(--space-4);">
                    <div @click="historyOpen = false" style="position: absolute; inset: 0; background: rgba(17,24,39,0.5);"></div>
                    <div class="panel" style="position: relative; width: 100%; max-width: 640px; max-height: 85vh; overflow-y: auto;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-4);">
                            <div>
                                <h4 style="margin: 0 0 2px;" x-text="historyData ? historyData.no_order : 'Memuat...'"></h4>
                                <div class="text-muted" style="font-size: 13px;" x-text="historyData ? historyData.customer : ''"></div>
                            </div>
                            <button type="button" @click="historyOpen = false" class="btn btn-secondary" style="height: 32px; padding: 0 12px;">Tutup</button>
                        </div>

                        <template x-if="historyLoading">
                            <div class="text-muted" style="padding: var(--space-6); text-align: center;">Memuat riwayat...</div>
                        </template>

                        <template x-if="!historyLoading && historyData">
                            <div style="display: flex; flex-direction: column; gap: var(--space-5);">
                                <div>
                                    <div class="label" style="margin-bottom: 6px;">Item &amp; posisi qty saat ini</div>
                                    <template x-for="item in historyData.items" :key="item.id">
                                        <div style="border: 1px solid var(--color-divider); padding: var(--space-3); margin-bottom: 8px;">
                                            <div style="font-weight: 600; margin-bottom: 4px;">
                                                <span x-text="item.name"></span>
                                                <template x-if="item.file">
                                                    <span class="text-muted" style="font-weight: 400;" x-text="'· ' + item.file"></span>
                                                </template>
                                                <span class="text-muted" style="font-weight: 400;">(Qty <span x-text="item.qty_total"></span>)</span>
                                            </div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                <template x-for="stage in item.stages.filter(s => s.qty > 0)" :key="stage.label">
                                                    <span class="tag tag-outline" x-text="stage.qty + ' di ' + stage.label"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <div class="label" style="margin-bottom: 10px;">Riwayat proses (terbaru dulu)</div>
                                    <div style="display: flex; flex-direction: column; max-height: 340px; overflow-y: auto; padding-right: 12px;">
                                        <template x-for="(h, idx) in historyData.history" :key="idx">
                                            <div style="display: flex; gap: 12px;">
                                                <!-- Kolom ikon + garis panah penghubung ke entri berikutnya -->
                                                <div style="display: flex; flex-direction: column; align-items: center; width: 28px; flex-shrink: 0;">
                                                    <div :style="`width:26px; height:26px; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1.5px solid ${historyIconFor(h).color}; background:color-mix(in srgb, ${historyIconFor(h).color} 12%, white); color:${historyIconFor(h).color};`">
                                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" x-html="historyIcons[historyIconFor(h).icon]"></svg>
                                                    </div>
                                                    <template x-if="idx < historyData.history.length - 1">
                                                        <div style="position: relative; width: 2px; flex: 1; min-height: 28px; background: var(--color-divider);">
                                                            <span style="position: absolute; left: 50%; bottom: -3px; transform: translateX(-50%); color: var(--color-divider); font-size: 11px; line-height: 1;">&#9662;</span>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Konten entri -->
                                                <div style="flex: 1; display: flex; justify-content: space-between; gap: var(--space-4); padding-bottom: 18px; font-size: 13px;">
                                                    <div>
                                                        <div>
                                                            <span style="font-weight: 600;" x-text="h.stage"></span>
                                                            <span class="text-muted"> &middot; </span>
                                                            <span x-text="(h.qty !== null ? h.qty + ' unit' : h.action)"></span>
                                                            <span class="text-muted" x-show="h.action === 'selesai'"> (tuntas di tahap ini)</span>
                                                        </div>
                                                        <template x-if="h.catatan">
                                                            <div class="text-muted" style="margin-top: 2px;" x-text="h.catatan"></div>
                                                        </template>
                                                    </div>
                                                    <div class="text-muted" style="white-space: nowrap; text-align: right; line-height: 1.5;">
                                                        <div x-text="h.created_at"></div>
                                                        <div style="margin-top: 2px;" x-text="h.user"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="historyData.history.length === 0">
                                            <div class="text-muted" style="padding: var(--space-4); text-align: center;">Belum ada riwayat.</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
