@props(['number', 'estimateDeadline' => null])

<span
    x-data="{
        copied: false,
        deadline: @js($estimateDeadline),
        now: Date.now(),
        timer: null,
        remainingHours() {
            return Math.max(0, Math.ceil((new Date(this.deadline).getTime() - this.now) / 3600000));
        },
        overdueHours() {
            return Math.max(1, Math.ceil((this.now - new Date(this.deadline).getTime()) / 3600000));
        },
        isOverdue() {
            return this.deadline && this.now > new Date(this.deadline).getTime();
        },
    }"
    x-init="if (deadline) timer = setInterval(() => now = Date.now(), 60000)"
    class="inline-flex items-center gap-1"
>
    <span>{{ $number }}</span>
    <button type="button"
            @click.stop="
                const t = document.createElement('textarea');
                t.value = '{{ $number }}';
                document.body.appendChild(t);
                t.select();
                t.setSelectionRange(0, 99999);
                document.execCommand('copy');
                document.body.removeChild(t);
                copied = true;
                setTimeout(() => copied = false, 1500);
            "
            :title="copied ? 'Tersalin!' : 'Pilih & salin nomor order'"
            class="shrink-0 text-gray-400 hover:text-gray-600">
        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
        </svg>
        <svg x-show="copied" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-green-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    </button>
    <span x-show="deadline" x-cloak
          :class="isOverdue() ? 'border-red-300 bg-red-50 text-red-700' : 'border-amber-300 bg-amber-50 text-amber-700'"
          class="ml-1 inline-flex rounded border px-1.5 py-0.5 text-[11px] font-bold tabular-nums whitespace-nowrap"
          :title="isOverdue() ? 'Estimasi pengerjaan sudah terlewati' : 'Sisa waktu sampai akhir estimasi'"
    >
        <svg x-show="isOverdue()" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"
             class="mr-1 h-3.5 w-3.5 animate-pulse text-orange-500"
             style="animation-duration:.55s;filter:drop-shadow(0 0 3px rgba(249,115,22,.75))">
            <path d="M13.5 2.1c.4 3.2-1.7 4.6-3.2 6.2-1.2 1.2-2.1 2.5-1.6 4.5.6-1.3 1.6-2.1 2.7-2.9-.2 2.1 1.5 3.2 2.2 4.8.5-1.1.7-2.3.3-3.6 1.8 1.4 3.1 3.3 3.1 5.7 0 3.1-2.3 5.2-5.2 5.2-3.5 0-6.3-2.6-6.3-6.2 0-2.7 1.4-4.7 3.2-6.5 2.1-2 4.1-3.8 4.8-7.2Z"/>
        </svg>
        <span x-text="isOverdue() ? `Lewat ${overdueHours()} Jam` : `-${remainingHours()} Jam`"></span>
    </span>
</span>
