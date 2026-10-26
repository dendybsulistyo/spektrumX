@props(['key'])

{{-- Pertahankan posisi daftar antrean (.operator-order-list) setelah item dikirim ke tahap
     berikutnya atau halaman dimuat ulang, agar operator tetap di sekitar order yang dipilih.
     Penanda posisi = nomor order pada kartu/baris yang terakhir diklik, sehingga tetap
     berfungsi walau form kirimnya berada di modal (mis. serah terima Pengambilan). --}}
<style>
    .queue-return-focus { animation: queue-return-focus 2.4s ease-out; }
    @keyframes queue-return-focus {
        0%, 30% { box-shadow: 0 0 0 3px #93c5fd, inset 4px 0 0 #2563eb; background-color: #eff6ff; }
        100% { box-shadow: 0 0 0 0 transparent, inset 4px 0 0 transparent; }
    }
</style>
<script>
    (() => {
        const storageKey = 'spektrumX:queue-position:{{ $key }}';
        const orderPattern = /\b(?:IND|OUT|ART|INV|DO|SO)\.\d\.\d{8,}(?:-\d+)?\b/;
        const list = () => document.querySelector('.operator-order-list');
        const currentTab = () => new URLSearchParams(location.search).get('tab') || '';
        const blockOf = (el) => el?.closest('.order-card, tr') || null;
        const orderNumberOf = (block) => block?.textContent.match(orderPattern)?.[0] || null;
        let lastAnchor = null;
        let submitted = false;

        // Ingat kartu/baris terakhir yang disentuh operator di dalam daftar.
        document.addEventListener('click', (event) => {
            const el = list();
            if (!el || !el.contains(event.target)) return;
            const block = blockOf(event.target);
            const number = orderNumberOf(block);
            if (number) lastAnchor = { number, top: block.getBoundingClientRect().top, at: Date.now() };
        }, true);

        const save = () => {
            const el = list();
            if (!el) return;
            const anchor = lastAnchor && Date.now() - lastAnchor.at < 600000 ? lastAnchor : null;
            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    path: location.pathname,
                    tab: currentTab(),
                    number: anchor?.number ?? null,
                    anchorTop: anchor?.top ?? null,
                    scrollTop: el.scrollTop,
                    savedAt: Date.now(),
                }));
            } catch (e) {}
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || (form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
            // Form di dalam daftar: pakai kartunya sebagai penanda (posisi saat ini).
            if (list()?.contains(form)) {
                const block = blockOf(form);
                const number = orderNumberOf(block);
                if (number) lastAnchor = { number, top: block.getBoundingClientRect().top, at: Date.now() };
            }
            submitted = true;
            save();
        }, true);
        // Muat ulang otomatis / manual tanpa kiriman form: simpan posisi scroll saja.
        window.addEventListener('beforeunload', () => { if (!submitted) { lastAnchor = null; save(); } });

        const restore = () => {
            let position = null;
            try {
                position = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
                sessionStorage.removeItem(storageKey);
            } catch (e) {}
            if (!position || position.path !== location.pathname || Date.now() - position.savedAt > 60000) return;
            if (position.tab && currentTab() && position.tab !== currentTab()) return;

            let focused = false;
            const apply = () => {
                const el = list();
                if (!el) return;
                const block = position.number
                    ? [...el.querySelectorAll('.order-card, tr')].find((b) => b.offsetParent !== null && b.textContent.includes(position.number))
                    : null;
                if (block && Number.isFinite(position.anchorTop)) {
                    el.scrollBy({ top: block.getBoundingClientRect().top - position.anchorTop, behavior: 'auto' });
                    if (!focused) {
                        focused = true;
                        block.classList.add('queue-return-focus');
                        setTimeout(() => block.classList.remove('queue-return-focus'), 2500);
                    }
                    return;
                }
                // Order sudah pindah tahap / hilang dari daftar: kembali ke posisi scroll terakhir.
                if (Math.abs(el.scrollTop - position.scrollTop) > 2) el.scrollTo({ top: position.scrollTop, behavior: 'auto' });
            };
            // Daftar bisa belum selesai tersusun saat awal dimuat; ulangi sampai pas.
            [60, 250, 600, 1200].forEach((delay) => setTimeout(apply, delay));
        };

        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', restore) : restore();
    })();
</script>
