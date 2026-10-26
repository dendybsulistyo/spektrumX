@props(['deadline' => null])

{{-- Sisa waktu / keterlambatan estimasi pengerjaan; dipakai antrean operator & Dashboard. --}}
@if ($deadline)
    <span x-data="{
              deadline: @js($deadline),
              now: Date.now(),
              remainingHours() { return Math.max(0, Math.ceil((new Date(this.deadline).getTime() - this.now) / 3600000)); },
              overdueHours() { return Math.max(1, Math.ceil((this.now - new Date(this.deadline).getTime()) / 3600000)); },
              isOverdue() { return this.now > new Date(this.deadline).getTime(); },
          }"
          x-init="setInterval(() => now = Date.now(), 60000)"
          :class="isOverdue() ? 'estimate-overdue-flash' : 'border-emerald-300 bg-emerald-50 text-emerald-700'"
          :title="isOverdue() ? 'Estimasi pengerjaan sudah terlewati' : 'Sisa waktu sampai akhir estimasi'"
          {{ $attributes->merge(['class' => 'ml-1 inline-flex rounded border px-1.5 py-0.5 text-[11px] font-bold tabular-nums whitespace-nowrap']) }}>
        <span x-text="isOverdue() ? `Lewat ${overdueHours()} Jam` : `-${remainingHours()} Jam`"></span>
    </span>
@endif
