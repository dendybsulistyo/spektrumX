<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Saran Gabung Outdoor</h2>
    </x-slot>

    @push('styles')
        <style>
            #saran-gabung { max-width: 1280px; margin: 0 auto; color: #1f2937; }
            #saran-gabung .sg-note { padding: 10px 14px; margin-bottom: 14px; border: 1px solid #fde68a; background: #fffbeb; border-radius: 8px; font-size: 13px; color: #78350f; }
            #saran-gabung .sg-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; font-size: 13px; }
            #saran-gabung .sg-tools input { width: 70px; padding: 4px 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
            #saran-gabung .sg-btn { padding: 6px 12px; border-radius: 6px; background: #1f2937; color: #fff; font-weight: 600; font-size: 13px; border: 0; cursor: pointer; text-decoration: none; }
            #saran-gabung .sg-btn.light { background: #fff; color: #1f2937; border: 1px solid #d1d5db; }
            #saran-gabung .sg-card { margin-bottom: 18px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; overflow: hidden; }
            #saran-gabung .sg-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
            #saran-gabung .sg-title { font-weight: 700; font-size: 15px; }
            #saran-gabung .sg-sub { font-size: 12px; color: #6b7280; }
            #saran-gabung .sg-eff { font-weight: 700; font-size: 15px; }
            #saran-gabung .sg-body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 16px; padding: 14px 16px; }
            #saran-gabung .sg-svg { width: 100%; max-height: 520px; border: 1px dashed #9ca3af; background: repeating-linear-gradient(45deg, #f9fafb 0 6px, #f3f4f6 6px 12px); }
            #saran-gabung table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            #saran-gabung th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
            #saran-gabung td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
            #saran-gabung .sg-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
            #saran-gabung .sg-warn { margin-top: 8px; padding: 6px 10px; border-radius: 6px; background: #fef2f2; color: #991b1b; font-size: 12.5px; }
            @media (max-width: 900px) { #saran-gabung .sg-body { grid-template-columns: minmax(0, 1fr); } }
        </style>
    @endpush

    @php
        $color = fn (string $key) => 'hsl('.(crc32($key) % 360).' 65% 62%)';
        $cm = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');
    @endphp

    <div id="saran-gabung">
        <div class="sg-note">
            <strong>Hanya saran.</strong> Halaman ini membaca antrean Cetak outdoor dan menyusun item dengan <strong>printer + bahan yang sama</strong> ke lebar roll.
            Tidak ada data yang diubah — keputusan gabung tetap di operator. Lebar roll dibaca dari nama bahan; ukuran dalam cm, item boleh diputar 90°.
        </div>

        <form method="GET" class="sg-tools">
            <a href="{{ route('order-cetak.index', ['tab' => 'outdoor']) }}" class="sg-btn light">&larr; Operator Cetak</a>
            <label>Margin tepi roll <input type="number" name="margin" min="0" max="50" step="0.5" value="{{ $margin }}"> cm</label>
            <label>Jarak antar item <input type="number" name="gap" min="0" max="50" step="0.5" value="{{ $gap }}"> cm</label>
            <button type="submit" class="sg-btn">Hitung ulang</button>
        </form>

        @forelse ($groups as $group)
            @php $plan = $group['plan']; @endphp
            <section class="sg-card">
                <div class="sg-head">
                    <div>
                        <div class="sg-title">{{ $group['printer'] }} &middot; {{ $group['material'] }}</div>
                        <div class="sg-sub">
                            {{ $group['items']->count() }} item &middot; {{ $group['items']->sum('qty') }} lembar
                            &middot; roll {{ $group['roll_width'] ? $cm($group['roll_width']).' cm' : 'belum diketahui' }}
                        </div>
                    </div>
                    @if ($plan && $plan['shelves'])
                        <div style="text-align: right;">
                            <div class="sg-eff" style="color: {{ $plan['efficiency'] >= .75 ? '#15803d' : ($plan['efficiency'] >= .5 ? '#a16207' : '#b91c1c') }};">
                                Efisiensi {{ number_format($plan['efficiency'] * 100, 0) }}%
                            </div>
                            <div class="sg-sub">
                                Bahan terpakai {{ $cm($plan['length'] / 100) }} m
                                &middot; sisa {{ $cm($group['roll_width'] * $plan['length'] * (1 - $plan['efficiency']) / 10000) }} m²
                            </div>
                        </div>
                    @endif
                </div>

                <div class="sg-body">
                    <div>
                        @if ($plan && $plan['shelves'])
                            <svg class="sg-svg" viewBox="0 0 {{ $group['roll_width'] }} {{ $plan['length'] }}" preserveAspectRatio="xMidYMin meet" role="img"
                                 aria-label="Susunan di roll {{ $cm($group['roll_width']) }} cm">
                                @foreach ($plan['shelves'] as $shelf)
                                    @foreach ($shelf['pieces'] as $piece)
                                        <g>
                                            <title>{{ $piece['label'] }} — {{ $cm($piece['w']) }} × {{ $cm($piece['h']) }} cm{{ $piece['rotated'] ? ' (diputar)' : '' }}</title>
                                            <rect x="{{ $piece['x'] }}" y="{{ $piece['y'] }}" width="{{ $piece['w'] }}" height="{{ $piece['h'] }}"
                                                  fill="{{ $color($piece['key']) }}" stroke="#1f2937" stroke-width="{{ max(0.5, $group['roll_width'] / 400) }}"></rect>
                                        </g>
                                    @endforeach
                                @endforeach
                            </svg>
                            <div class="sg-sub" style="margin-top: 4px;">Lebar gambar = lebar roll; arah ke bawah = panjang bahan. Arahkan kursor ke kotak untuk detail.</div>
                        @elseif (! $group['roll_width'])
                            <div class="sg-warn">Lebar roll bahan ini tidak tertulis di nama bahan, jadi susunan belum bisa dihitung.</div>
                        @endif
                        @if ($plan && $plan['too_wide'])
                            <div class="sg-warn">
                                Tidak muat lebar roll (lewat {{ $cm($plan['usable_width']) }} cm efektif):
                                @foreach ($plan['too_wide'] as $wide){{ $loop->first ? '' : ', ' }}{{ $wide['label'] }} ({{ $cm($wide['width']) }} × {{ $cm($wide['height']) }})@endforeach
                            </div>
                        @endif
                        @if ($plan && $plan['truncated'])
                            <div class="sg-warn">Lembar terlalu banyak; hanya {{ \App\Support\OutdoorNestingPlanner::MAX_PIECES }} lembar pertama yang disusun.</div>
                        @endif
                    </div>

                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr><th>No. order</th><th>Customer</th><th>File</th><th>P × L</th><th>Qty</th><th>Umur</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($group['items'] as $item)
                                    <tr>
                                        <td style="white-space: nowrap;"><span class="sg-swatch" style="background: {{ $color($item['key']) }}"></span>{{ $item['no_order'] }}</td>
                                        <td>{{ $item['customer'] }}</td>
                                        <td>{{ $item['file'] }}@if ($item['gabungan'])<div class="sg-sub">gabungan: {{ $item['gabungan'] }}</div>@endif</td>
                                        <td style="white-space: nowrap;">{{ $item['size'] }}</td>
                                        <td>{{ $item['qty'] }}</td>
                                        <td style="white-space: nowrap;">{{ $item['age'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @empty
            <div class="sg-card" style="padding: 24px; text-align: center; color: #6b7280;">Tidak ada item outdoor di antrean Cetak.</div>
        @endforelse
    </div>
</x-app-layout>
