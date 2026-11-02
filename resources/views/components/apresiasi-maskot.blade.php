{{--
    Maskot "MOMON" (dari monitor) — bintang tinta khas Spektrum yang lewat tiap pergantian jam
    dan berterima kasih pada yang aktif sejam terakhir. Semua animasi di browser;
    server hanya memberi rekap yang di-cache per jam (ApresiasiController).
--}}
<div id="apresiasi" aria-live="polite" hidden>
    <div class="apr-walker">
        <div class="apr-bubble">
            <strong>Terima kasih</strong> <span class="apr-names"></span>
            <div class="apr-stats"></div>
            <div class="apr-sign">— MOMON</div>
        </div>
        <svg class="apr-maskot" viewBox="0 0 120 130" role="img" aria-label="MOMON, maskot Spektrum">
            {{-- kaki --}}
            <g class="apr-leg-l"><path d="M42 106 q-4 10 -13 14 h19 q2 -6 3 -13z" fill="#e8418f"/></g>
            <g class="apr-leg-r"><path d="M78 106 q4 10 13 14 h-19 q-2 -6 -3 -13z" fill="#e8418f"/></g>
            {{-- badan bintang gendut --}}
            <path d="M60 12 C71 12 74 32 82 37 C92 41 116 40 117 51 C118 62 97 70 96 81 C96 93 106 109 97 116 C88 122 71 108 60 108 C49 108 32 122 23 116 C14 109 24 93 24 81 C23 70 2 62 3 51 C4 40 28 41 38 37 C46 32 49 12 60 12 Z"
                  fill="#f472b6" stroke="#be185d" stroke-width="2.5" stroke-linejoin="round"/>
            {{-- perut buncit --}}
            <ellipse cx="60" cy="78" rx="30" ry="22" fill="#f9a8d4" opacity=".55"/>
            {{-- celana cyan bertitik tinta --}}
            <path d="M28 84 C42 92 78 92 92 84 L96 97 C80 107 40 107 24 97 Z" fill="#06b6d4" stroke="#0e7490" stroke-width="2"/>
            <circle cx="44" cy="96" r="2.6" fill="#facc15"/><circle cx="60" cy="99" r="2.6" fill="#111827"/><circle cx="76" cy="96" r="2.6" fill="#e8418f"/>
            {{-- mata --}}
            <ellipse cx="51" cy="50" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.5"/>
            <ellipse cx="69" cy="50" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.5"/>
            <circle class="apr-pupil" cx="52" cy="52" r="3" fill="#111827"/>
            <circle class="apr-pupil" cx="70" cy="52" r="3" fill="#111827"/>
            {{-- kacamata --}}
            <g fill="rgba(186,230,253,.25)" stroke="#111827" stroke-width="2.4">
                <circle cx="50" cy="50" r="10.5"/>
                <circle cx="71" cy="50" r="10.5"/>
            </g>
            <path d="M60.5 49 q0 -3 0 0" stroke="#111827" stroke-width="2.4" fill="none"/>
            <path d="M39.5 48 L30 45 M81.5 48 L91 45" stroke="#111827" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M44 44 q3 -3 6 -2 M65 44 q3 -3 6 -2" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round" opacity=".8"/>
            {{-- pipi & senyum --}}
            <ellipse cx="42" cy="62" rx="5" ry="3" fill="#fde047" opacity=".85"/>
            <ellipse cx="78" cy="62" rx="5" ry="3" fill="#fde047" opacity=".85"/>
            <path d="M48 64 Q60 76 72 64" fill="#7f1d1d" stroke="#111827" stroke-width="1.5"/>
            <path d="M53 68 Q60 72 67 68" fill="#fb7185"/>
            {{-- tangan melambai --}}
            <g class="apr-arm"><path d="M108 48 q8 -10 9 -20" stroke="#be185d" stroke-width="2.5" fill="none" stroke-linecap="round"/></g>
        </svg>
    </div>
</div>

<style>
    #apresiasi { position: fixed; inset: auto 0 0 0; height: 0; z-index: 60; pointer-events: none; }
    #apresiasi .apr-walker { position: absolute; left: 0; bottom: 8px; display: flex; flex-direction: column; align-items: flex-start; pointer-events: auto; cursor: pointer;
        transform: translateX(-180px); animation: apr-walk 22s linear forwards; }
    #apresiasi .apr-maskot { width: 104px; height: 108px; animation: apr-dance .5s ease-in-out infinite alternate; transform-origin: 50% 100%; filter: drop-shadow(0 6px 6px rgba(0,0,0,.18)); }
    #apresiasi .apr-leg-l { animation: apr-step .5s ease-in-out infinite alternate; transform-origin: 46px 104px; }
    #apresiasi .apr-leg-r { animation: apr-step .5s ease-in-out infinite alternate-reverse; transform-origin: 74px 104px; }
    #apresiasi .apr-arm { animation: apr-wave .35s ease-in-out infinite alternate; transform-origin: 108px 48px; }
    #apresiasi .apr-bubble { max-width: min(520px, 85vw); margin: 0 0 6px 40px; padding: 9px 12px; background: #fff; color: #111827; border: 2px solid #111827; border-radius: 14px;
        font-size: 13px; line-height: 1.35; box-shadow: 3px 3px 0 #06b6d4; position: relative; }
    #apresiasi .apr-bubble::after { content: ""; position: absolute; left: 22px; bottom: -9px; width: 14px; height: 14px; background: #fff; border-right: 2px solid #111827; border-bottom: 2px solid #111827; transform: rotate(45deg); }
    #apresiasi .apr-sign { margin-top: 2px; font-size: 11px; font-weight: 700; letter-spacing: .08em; color: #0e7490; text-align: right; }
    #apresiasi .apr-stats { margin-top: 3px; font-size: 12px; color: #be185d; font-weight: 600; }
    @keyframes apr-walk { 0% { transform: translateX(-180px); } 100% { transform: translateX(calc(100vw + 40px)); } }
    @keyframes apr-dance { from { transform: rotate(-7deg) translateY(0); } to { transform: rotate(7deg) translateY(-8px); } }
    @keyframes apr-step { from { transform: rotate(-14deg); } to { transform: rotate(14deg); } }
    @keyframes apr-wave { from { transform: rotate(-20deg); } to { transform: rotate(25deg); } }
    @media (prefers-reduced-motion: reduce) {
        #apresiasi .apr-walker { animation: none; transform: translateX(16px); }
        #apresiasi .apr-maskot, #apresiasi .apr-leg-l, #apresiasi .apr-leg-r, #apresiasi .apr-arm { animation: none; }
    }
    @media print { #apresiasi { display: none !important; } }
</style>

<script>
    (() => {
        const root = document.getElementById('apresiasi');
        if (!root) return;
        const url = @js(route('apresiasi'));
        const storeKey = 'spektrum-apresiasi-terakhir';
        const read = () => { try { return localStorage.getItem(storeKey); } catch { return null; } };
        const write = (value) => { try { localStorage.setItem(storeKey, value); } catch {} };
        const fmt = (n) => Number(n).toLocaleString('id-ID');

        // Jangan ganggu: sedang mengetik, ada dialog/modal terbuka, atau tab tidak terlihat.
        const busy = () => {
            const el = document.activeElement;
            if (el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName))) return true;
            if (document.hidden) return true;
            return [...document.querySelectorAll('dialog[open], [aria-modal="true"], [role="dialog"]')]
                .some((m) => m.offsetParent !== null || getComputedStyle(m).position === 'fixed' && getComputedStyle(m).display !== 'none');
        };

        const show = (data) => {
            const names = data.names.join(', ') + (data.more > 0 ? ` +${data.more} lainnya` : '');
            root.querySelector('.apr-names').textContent = names + '!';
            const parts = [];
            if (data.progress) parts.push(`${fmt(data.progress)} progress`);
            if (data.pembayaran) parts.push(`${fmt(data.pembayaran)} pembayaran`);
            if (data.lembar_kerja) parts.push(`${fmt(data.lembar_kerja)} lembar kerja`);
            root.querySelector('.apr-stats').textContent = parts.join(', ') + ' jam ini 🎉';
            const walker = root.querySelector('.apr-walker');
            walker.style.animation = 'none'; void walker.offsetWidth; walker.style.animation = '';
            root.hidden = false;
            const hide = () => { root.hidden = true; };
            walker.onclick = hide;
            walker.onanimationend = (e) => { if (e.animationName === 'apr-walk') hide(); };
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) setTimeout(hide, 12000);
        };

        const attempt = async (tries = 0) => {
            if (busy()) {
                if (tries < 20) setTimeout(() => attempt(tries + 1), 30000);
                return;
            }
            try {
                const { data } = await axios.get(url);
                if (!data.aktif || read() === data.jam) return;
                write(data.jam); // satu kali per jam walau banyak tab terbuka
                if (data.names && data.names.length) show(data);
            } catch {}
        };

        const scheduleNext = () => {
            const now = new Date();
            const next = new Date(now); next.setHours(now.getHours() + 1, 0, 0, 0);
            // Jeda acak 5–45 detik agar tidak semua komputer meminta bersamaan.
            setTimeout(() => { attempt(); scheduleNext(); }, next - now + 5000 + Math.random() * 40000);
        };

        // Coba tampilan tanpa menunggu jam: tambahkan ?momon=coba di alamat halaman (data contoh, tidak ke server).
        if (new URLSearchParams(location.search).get('momon') === 'coba') {
            setTimeout(() => show({ names: ['Adisa', 'Zildjian', 'Sanjay', 'Rina', 'Dewi', 'Bagus', 'Lia', 'Fajar'], more: 0, progress: 124, pembayaran: 18, lembar_kerja: 9 }), 800);
        }

        // Baru buka/muat ulang halaman di 10 menit awal jam: tetap kebagian (kalau belum tampil).
        if (new Date().getMinutes() < 10) setTimeout(() => attempt(), 3000 + Math.random() * 5000);
        scheduleNext();
    })();
</script>
