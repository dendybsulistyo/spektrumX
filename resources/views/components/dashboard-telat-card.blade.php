@props(['telat'])

{{-- Kotak order telat dashboard; membuka modal resume lewat openOverdue() milik #rc-dashboard. --}}
<button type="button" @click="openOverdue()" {{ $attributes->class(['rcd-kpi alert', 'calm' => (int) $telat === 0, 'flashing' => (int) $telat > 0]) }}
        title="Order yang lewat 24 jam sejak dibayar dan belum siap diambil">
    <div class="rcd-kpi-label">Telat &gt; 1&times;24 jam</div>
    <div class="rcd-kpi-value">{{ number_format($telat, 0, ',', '.') }}</div>
    <div class="rcd-kpi-meta">{{ (int) $telat === 0 ? 'Semua order sesuai jadwal' : 'Sudah dibayar, belum siap diambil' }}</div>
</button>
