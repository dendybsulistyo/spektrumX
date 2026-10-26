@props(['deadline' => null, 'compact' => false])

{{-- Sisa waktu / keterlambatan estimasi pengerjaan; dipakai antrean operator & Dashboard.
     compact = titik berkedip tanpa teks (Dashboard), teks tampil sebagai tooltip. --}}
@if ($deadline)
    <span x-data="{
              deadline: @js($deadline),
              now: Date.now(),
              remainingHours() { return Math.max(0, Math.ceil((new Date(this.deadline).getTime() - this.now) / 3600000)); },
              overdueHours() { return Math.max(1, Math.ceil((this.now - new Date(this.deadline).getTime()) / 3600000)); },
              isOverdue() { return this.now > new Date(this.deadline).getTime(); },
          }"
          x-init="setInterval(() => now = Date.now(), 60000)"
          @if ($compact)
              :class="isOverdue() ? 'estimate-dot-overdue' : 'estimate-dot-ok'"
              :title="isOverdue() ? `Lewat ${overdueHours()} jam dari estimasi` : `Sisa ${remainingHours()} jam sampai akhir estimasi`"
              {{ $attributes->merge(['class' => 'estimate-dot']) }}
          @else
              :class="isOverdue() ? 'estimate-overdue-flash' : 'border-emerald-300 bg-emerald-50 text-emerald-700'"
              :title="isOverdue() ? 'Estimasi pengerjaan sudah terlewati' : 'Sisa waktu sampai akhir estimasi'"
              {{ $attributes->merge(['class' => 'ml-1 inline-flex rounded border px-1.5 py-0.5 text-[11px] font-bold tabular-nums whitespace-nowrap']) }}
          @endif
    >@unless ($compact)<span x-text="isOverdue() ? `Lewat ${overdueHours()} Jam` : `-${remainingHours()} Jam`"></span>@endunless</span>

    @if ($compact)
        @once
            <style>
                .estimate-dot { display:inline-block; width:10px; height:10px; margin-left:6px; border-radius:9999px; vertical-align:middle; cursor:help; }
                .estimate-dot-ok { background:#34d399; box-shadow:0 0 0 2px #d1fae5; }
                .estimate-dot-overdue { background:#dc2626; animation:estimate-dot-flash .7s ease-in-out infinite; }
                @keyframes estimate-dot-flash {
                    0%, 100% { background:#dc2626; box-shadow:0 0 0 2px #fecaca; }
                    50% { background:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.35); }
                }
                @media (prefers-reduced-motion: reduce) { .estimate-dot-overdue { animation:none; } }
            </style>
        @endonce
    @endif
@endif
