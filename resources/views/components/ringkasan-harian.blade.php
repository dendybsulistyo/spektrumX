{{-- Ringkasan hari ini (operator, CS, kasir) — data dimuat saat menu user dibuka. --}}
<div class="rh">
    <div class="rh-head">
        <span>Ringkasan hari ini</span>
        <small x-text="summary?.date ?? ''"></small>
    </div>
    <template x-if="summaryLoading && !summary"><div class="rh-empty">Menghitung…</div></template>
    <template x-for="section in (summary?.sections ?? [])" :key="section.kind">
        <div class="rh-section">
            <div class="rh-title" x-text="section.title" x-show="(summary?.sections ?? []).length > 1"></div>
            <div class="rh-stats">
                <template x-for="stat in section.stats" :key="stat.label">
                    <div class="rh-stat">
                        <b x-text="stat.money ? 'Rp ' + Number(stat.value).toLocaleString('id-ID') : Number(stat.value).toLocaleString('id-ID')"
                           :class="stat.money && 'is-money'"></b>
                        <span x-text="stat.label"></span>
                    </div>
                </template>
            </div>
            <template x-if="section.methods && section.methods.length">
                <div class="rh-methods">
                    <template x-for="m in section.methods" :key="m.label">
                        <span><i x-text="m.label"></i> <b x-text="'Rp ' + Number(m.total).toLocaleString('id-ID')"></b></span>
                    </template>
                </div>
            </template>
            <div class="rh-trend" x-show="section.trend" :class="section.trend && 'is-' + section.trend.direction" x-text="section.trend?.text"></div>
            <div class="rh-note" x-show="section.note" x-text="section.note"></div>
        </div>
    </template>
</div>

@once
    <style>
        .rh { margin: -4px 0 4px; padding: 10px 12px 8px; background: #faf7f0; border-bottom: 1px dashed #cfc7b5; border-radius: 8px 8px 0 0; font-family: 'IBM Plex Sans', system-ui, sans-serif; }
        .rh-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
        .rh-head span { font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #1b2236; }
        .rh-head small { font-size: 11px; color: #77736a; }
        .rh-empty { padding: 6px 0; font-size: 12px; color: #77736a; }
        .rh-section + .rh-section { margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e3ddcf; }
        .rh-title { margin-bottom: 4px; font-size: 11px; font-weight: 600; color: #77736a; }
        .rh-stats { display: flex; flex-wrap: wrap; gap: 4px 14px; }
        .rh-stat { display: flex; flex-direction: column; }
        .rh-stat b { font-family: 'IBM Plex Mono', monospace; font-size: 20px; font-weight: 600; line-height: 1.15; color: #1b2236; }
        .rh-stat b.is-money { font-size: 16px; }
        .rh-stat span { font-size: 11px; color: #77736a; }
        .rh-methods { display: flex; flex-wrap: wrap; gap: 4px 10px; margin-top: 6px; font-size: 11.5px; }
        .rh-methods i { font-style: normal; color: #77736a; }
        .rh-methods b { font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: #1b2236; }
        .rh-trend { margin-top: 6px; font-size: 12px; font-weight: 600; color: #77736a; }
        .rh-trend.is-up { color: #2e7a4f; }
        .rh-trend.is-down { color: #a3124a; }
        .rh-note { margin-top: 2px; font-size: 11.5px; color: #b07d00; }
    </style>
@endonce
