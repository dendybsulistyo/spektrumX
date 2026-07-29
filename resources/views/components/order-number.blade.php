@props(['number'])

<span x-data="{ copied: false }" class="inline-flex items-center gap-1">
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
</span>
