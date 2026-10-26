@props(['key'])

{{-- Pertahankan posisi daftar antrean (.operator-order-list) setelah item dikirim ke tahap
     berikutnya atau halaman dimuat ulang, agar operator tetap di sekitar order yang dipilih.
     Penanda posisi = form kirim item (action unik per item), jadi tidak perlu ID di kartu. --}}
<style>
    .queue-return-focus { animation: queue-return-focus 2.4s ease-out; }
    @keyframes queue-return-focus {
        0%, 30% { box-shadow: 0 0 0 3px #93c5fd, inset 4px 0 0 #2563eb; }
        100% { box-shadow: 0 0 0 0 transparent, inset 4px 0 0 transparent; }
    }
</style>
<script>
    (() => {
        const storageKey = 'spektrumX:queue-position:{{ $key }}';
        const list = () => document.querySelector('.operator-order-list');
        const currentTab = () => new URLSearchParams(location.search).get('tab') || '';
        let submitted = false;

        const save = (form = null) => {
            const el = list();
            if (!el) return;
            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    path: location.pathname,
                    tab: currentTab(),
                    action: form ? form.getAttribute('action') : null,
                    anchorTop: form ? form.getBoundingClientRect().top : null,
                    scrollTop: el.scrollTop,
                    savedAt: Date.now(),
                }));
            } catch (e) {}
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form instanceof HTMLFormElement && list()?.contains(form)) {
                submitted = true;
                save(form);
            }
        }, true);
        // Muat ulang otomatis / manual: simpan posisi scroll saja.
        window.addEventListener('beforeunload', () => { if (!submitted) save(); });

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
                const anchor = position.action
                    ? [...el.querySelectorAll('form')].find((form) => form.getAttribute('action') === position.action && form.offsetParent !== null)
                    : null;
                if (anchor && Number.isFinite(position.anchorTop)) {
                    el.scrollBy({ top: anchor.getBoundingClientRect().top - position.anchorTop, behavior: 'auto' });
                    if (!focused) {
                        focused = true;
                        const card = anchor.closest('.order-card') || anchor.parentElement;
                        card?.classList.add('queue-return-focus');
                        setTimeout(() => card?.classList.remove('queue-return-focus'), 2500);
                    }
                    return;
                }
                // Item sudah habis terkirim: kembali ke posisi scroll terakhir.
                if (Math.abs(el.scrollTop - position.scrollTop) > 2) el.scrollTo({ top: position.scrollTop, behavior: 'auto' });
            };
            // Daftar bisa belum selesai tersusun saat awal dimuat; ulangi sampai pas.
            [60, 250, 600, 1200].forEach((delay) => setTimeout(apply, delay));
        };

        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', restore) : restore();
    })();
</script>
