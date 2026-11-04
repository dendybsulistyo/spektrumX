<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Employee of the Month</h2></x-slot>

    <style>
        #eotm .card { border:1px solid #e2e8f0; border-radius:10px; background:#fff; overflow:hidden; }
        #eotm .winner { display:flex; align-items:center; gap:12px; padding:14px 16px; background:linear-gradient(90deg,#fef9c3,#fff); border-bottom:1px solid #f1f5f9; }
        #eotm .trophy { font-size:30px; line-height:1; }
        #eotm table { width:100%; border-collapse:collapse; font-size:12.5px; }
        #eotm th { padding:6px 10px; background:#f8fafc; color:#64748b; font-size:10.5px; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        #eotm td { padding:6px 10px; border-top:1px solid #f1f5f9; white-space:nowrap; }
        #eotm .r { text-align:right; font-variant-numeric:tabular-nums; }
        #eotm tr.ineligible td { color:#94a3b8; }
    </style>

    <div id="eotm" class="mx-auto max-w-[1400px] space-y-4">
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded border border-slate-200 bg-white p-4">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">Bulan
                <input type="month" name="bulan" value="{{ $bulan }}" class="h-[34px] rounded border border-slate-300 px-2 text-sm">
            </label>
            <button class="h-[34px] rounded bg-slate-800 px-4 text-sm font-semibold text-white">Tampilkan</button>
            <p class="text-xs text-slate-500" style="max-width:720px;">
                Untuk seru-seruan, 100% dari data sistem. Skor = Produktivitas 50% + Tepat waktu 30% + Kualitas (tanpa ulang proses) 20%;
                divisi Pengambilan, Kasir & CS memakai produktivitas saja. Produktivitas dibandingkan dengan rekan satu divisi (tertinggi = 100).
                Minimal <strong>{{ $min_days }}</strong> hari aktif untuk bisa menang.
            </p>
        </form>

        @if ($provisional)
            <div class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                Bulan {{ $month->locale('id')->translatedFormat('F Y') }} masih berjalan: peringkat ini <strong>sementara</strong>
                (batas hari aktif disesuaikan menjadi {{ $min_days }} hari; nanti {{ $minDaysDefault }} hari).
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($divisions as $division)
                <section class="card">
                    <div class="winner">
                        <span class="trophy">{{ $division['winner'] ? '🏆' : '🥚' }}</span>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $division['label'] }}</div>
                            @if ($division['winner'])
                                <div class="text-lg font-bold text-slate-900">{{ ucwords(mb_strtolower($division['winner']['name'])) }}</div>
                                <div class="text-xs text-slate-600">Skor {{ number_format($division['winner']['score'], 1, ',', '.') }} ·
                                    {{ number_format($division['winner']['output'], 0, ',', '.') }} {{ $division['unit'] }} · {{ $division['winner']['active_days'] }} hari aktif</div>
                            @else
                                <div class="text-sm text-slate-500">Belum ada pemenang bulan ini.</div>
                            @endif
                        </div>
                    </div>
                    @if ($division['rows']->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table>
                                <thead><tr>
                                    <th>#</th><th>Nama</th><th class="r">Hari aktif</th><th class="r">{{ ucfirst($division['unit']) }}</th><th class="r">Produktivitas</th>
                                    @if ($division['weighted'])<th class="r">Tepat waktu</th><th class="r">Kualitas</th>@endif
                                    <th class="r">Skor</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($division['rows'] as $row)
                                        <tr @class(['ineligible' => ! $row['eligible']]) title="{{ $row['eligible'] ? '' : 'Hari aktif kurang dari '.$min_days }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ ucwords(mb_strtolower($row['name'])) }}</td>
                                            <td class="r">{{ $row['active_days'] }}</td>
                                            <td class="r">{{ number_format($row['output'], 0, ',', '.') }}</td>
                                            <td class="r">{{ number_format($row['productivity'], 1, ',', '.') }}</td>
                                            @if ($division['weighted'])
                                                <td class="r" title="{{ $row['late'] }} order telat dari {{ $row['orders'] }}">{{ number_format($row['on_time'], 1, ',', '.') }}%</td>
                                                <td class="r" title="{{ $row['rework'] }} ulang proses dari {{ $row['orders'] }} order">{{ number_format($row['quality'], 1, ',', '.') }}%</td>
                                            @endif
                                            <td class="r font-semibold">{{ number_format($row['score'], 1, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
