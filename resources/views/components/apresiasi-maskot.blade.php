{{--
    Maskot apresiasi Spektrum — tiap pergantian jam satu karakter (acak, tidak sama dengan
    sebelumnya) lewat di bawah layar dan berterima kasih pada yang aktif sejam terakhir.
    Karakter: bintang tinta, Si Tinta (CMYK), roll banner, printer, gunting & mata ayam, ular hijau.
    Semua animasi di browser; server hanya memberi rekap yang di-cache per jam (ApresiasiController).
--}}
<div id="apresiasi" aria-live="polite" hidden>
    <div class="apr-walker">
        <div class="apr-bubble">
            <strong>Terima kasih</strong> <span class="apr-names"></span>
            <div class="apr-stats"></div>
        </div>

        {{-- 1. Bintang tinta berkacamata --}}
        <svg class="apr-char" data-char="bintang" viewBox="0 0 120 130" role="img" aria-label="Maskot bintang tinta Spektrum">
            <g class="apr-leg-l"><path d="M42 106 q-4 10 -13 14 h19 q2 -6 3 -13z" fill="#e8418f"/></g>
            <g class="apr-leg-r"><path d="M78 106 q4 10 13 14 h-19 q-2 -6 -3 -13z" fill="#e8418f"/></g>
            <path d="M60 12 C71 12 74 32 82 37 C92 41 116 40 117 51 C118 62 97 70 96 81 C96 93 106 109 97 116 C88 122 71 108 60 108 C49 108 32 122 23 116 C14 109 24 93 24 81 C23 70 2 62 3 51 C4 40 28 41 38 37 C46 32 49 12 60 12 Z"
                  fill="#f472b6" stroke="#be185d" stroke-width="2.5" stroke-linejoin="round"/>
            <ellipse cx="60" cy="78" rx="30" ry="22" fill="#f9a8d4" opacity=".55"/>
            <path d="M28 84 C42 92 78 92 92 84 L96 97 C80 107 40 107 24 97 Z" fill="#06b6d4" stroke="#0e7490" stroke-width="2"/>
            <circle cx="44" cy="96" r="2.6" fill="#facc15"/><circle cx="60" cy="99" r="2.6" fill="#111827"/><circle cx="76" cy="96" r="2.6" fill="#e8418f"/>
            <ellipse cx="51" cy="50" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.5"/>
            <ellipse cx="69" cy="50" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.5"/>
            <circle cx="52" cy="52" r="3" fill="#111827"/><circle cx="70" cy="52" r="3" fill="#111827"/>
            <g fill="rgba(186,230,253,.25)" stroke="#111827" stroke-width="2.4"><circle cx="50" cy="50" r="10.5"/><circle cx="71" cy="50" r="10.5"/></g>
            <path d="M39.5 48 L30 45 M81.5 48 L91 45" stroke="#111827" stroke-width="2.2" stroke-linecap="round"/>
            <ellipse cx="42" cy="62" rx="5" ry="3" fill="#fde047" opacity=".85"/><ellipse cx="78" cy="62" rx="5" ry="3" fill="#fde047" opacity=".85"/>
            <path d="M48 64 Q60 76 72 64" fill="#7f1d1d" stroke="#111827" stroke-width="1.5"/><path d="M53 68 Q60 72 67 68" fill="#fb7185"/>
            <g class="apr-arm"><path d="M108 48 q8 -10 9 -20" stroke="#be185d" stroke-width="2.5" fill="none" stroke-linecap="round"/></g>
        </svg>

        {{-- 2. Si Tinta: empat tetes CMYK melompat bergantian --}}
        <svg class="apr-char apr-char-wide" data-char="tinta" viewBox="0 0 200 130" role="img" aria-label="Empat tetes tinta CMYK">
            @foreach ([['#06b6d4', '#0e7490', 0], ['#ec4899', '#be185d', 1], ['#facc15', '#a16207', 2], ['#1f2937', '#000', 3]] as [$fill, $edge, $i])
                <g class="apr-drop" style="--i: {{ $i }}" transform="translate({{ 8 + $i * 47 }} 0)">
                    <path d="M22 40 C30 56 42 70 42 86 C42 100 33 110 22 110 C11 110 2 100 2 86 C2 70 14 56 22 40 Z" fill="{{ $fill }}" stroke="{{ $edge }}" stroke-width="2"/>
                    <ellipse cx="14" cy="80" rx="3" ry="6" fill="#fff" opacity=".35"/>
                    <ellipse cx="16" cy="86" rx="4" ry="4.5" fill="#fff"/><ellipse cx="28" cy="86" rx="4" ry="4.5" fill="#fff"/>
                    <circle cx="17" cy="87" r="2" fill="#111827"/><circle cx="29" cy="87" r="2" fill="#111827"/>
                    <path d="M17 96 Q22 101 27 96" fill="none" stroke="{{ $i === 3 ? '#fff' : '#111827' }}" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M14 110 v8 M30 110 v8" stroke="{{ $edge }}" stroke-width="3" stroke-linecap="round"/>
                </g>
            @endforeach
        </svg>

        {{-- 3. Roll banner menggelinding sambil menarik lembar banner --}}
        <svg class="apr-char apr-char-wide" data-char="roll" viewBox="0 0 200 130" role="img" aria-label="Gulungan banner">
            <g class="apr-flag">
                <path d="M8 58 C30 50 50 66 72 58 C88 52 98 56 108 60 L108 96 C98 92 88 88 72 94 C50 102 30 86 8 94 Z" fill="#fff" stroke="#111827" stroke-width="2"/>
                <text x="56" y="82" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="900" letter-spacing="1" fill="#0e7490">SPEKTRUM</text>
            </g>
            <ellipse cx="150" cy="122" rx="30" ry="4" fill="#000" opacity=".12"/>
            <g class="apr-wheel" style="transform-origin: 150px 78px">
                <circle cx="150" cy="78" r="40" fill="#fef9c3" stroke="#111827" stroke-width="2.5"/>
                <path d="M150 78 m-6 0 a6 6 0 1 1 12 0 a12 12 0 1 1 -24 0 a18 18 0 1 1 36 0 a24 24 0 1 1 -48 0 a30 30 0 1 1 60 0" fill="none" stroke="#f59e0b" stroke-width="2.4"/>
            </g>
            <ellipse cx="138" cy="66" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.6"/><ellipse cx="162" cy="66" rx="7" ry="8" fill="#fff" stroke="#111827" stroke-width="1.6"/>
            <circle cx="140" cy="68" r="3" fill="#111827"/><circle cx="164" cy="68" r="3" fill="#111827"/>
            <path d="M140 86 Q150 96 160 86" fill="#7f1d1d" stroke="#111827" stroke-width="1.5"/>
            <ellipse cx="130" cy="80" rx="5" ry="3" fill="#fb7185" opacity=".7"/><ellipse cx="170" cy="80" rx="5" ry="3" fill="#fb7185" opacity=".7"/>
        </svg>

        {{-- 4. Si Printer berkaki, kertasnya naik-turun --}}
        <svg class="apr-char" data-char="printer" viewBox="0 0 120 130" role="img" aria-label="Printer kecil">
            <g class="apr-paper"><rect x="34" y="10" width="52" height="38" rx="2" fill="#fff" stroke="#111827" stroke-width="2"/>
                <rect x="40" y="17" width="16" height="10" fill="#06b6d4"/><rect x="58" y="17" width="10" height="10" fill="#ec4899"/><rect x="70" y="17" width="10" height="10" fill="#facc15"/>
                <path d="M40 33 h40 M40 39 h28" stroke="#9ca3af" stroke-width="2"/></g>
            <g class="apr-leg-l"><path d="M36 104 v14 h-8" stroke="#111827" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></g>
            <g class="apr-leg-r"><path d="M84 104 v14 h8" stroke="#111827" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></g>
            <rect x="12" y="40" width="96" height="66" rx="12" fill="#e5e7eb" stroke="#111827" stroke-width="2.5"/>
            <rect x="24" y="40" width="72" height="8" rx="3" fill="#9ca3af"/>
            <rect x="28" y="56" width="64" height="26" rx="6" fill="#0f172a"/>
            <rect class="apr-blink" x="40" y="62" width="10" height="12" rx="3" fill="#34d399"/><rect class="apr-blink" x="70" y="62" width="10" height="12" rx="3" fill="#34d399"/>
            <path d="M50 92 Q60 100 70 92" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
            <circle cx="96" cy="94" r="3.5" fill="#22c55e"/>
        </svg>

        {{-- 5. Duo finishing: gunting & mata ayam --}}
        <svg class="apr-char apr-char-wide" data-char="gunting" viewBox="0 0 200 130" role="img" aria-label="Gunting dan mata ayam">
            <g transform="translate(10 0)">
                <g class="apr-blade-a" style="transform-origin: 62px 64px"><path d="M62 64 L118 34 L120 40 Z" fill="#cbd5e1" stroke="#111827" stroke-width="2" stroke-linejoin="round"/></g>
                <g class="apr-blade-b" style="transform-origin: 62px 64px"><path d="M62 64 L118 94 L120 88 Z" fill="#cbd5e1" stroke="#111827" stroke-width="2" stroke-linejoin="round"/></g>
                <circle cx="34" cy="44" r="22" fill="#ef4444" stroke="#111827" stroke-width="2.5"/><circle cx="34" cy="44" r="10" fill="#fff" stroke="#111827" stroke-width="2"/>
                <circle cx="34" cy="86" r="22" fill="#ef4444" stroke="#111827" stroke-width="2.5"/><circle cx="34" cy="86" r="10" fill="#fff" stroke="#111827" stroke-width="2"/>
                <circle cx="62" cy="64" r="5" fill="#111827"/>
                <circle cx="28" cy="40" r="2.4" fill="#111827"/><circle cx="40" cy="40" r="2.4" fill="#111827"/>
                <path d="M28 49 Q34 54 40 49" fill="none" stroke="#111827" stroke-width="1.6" stroke-linecap="round"/>
            </g>
            <g class="apr-hop" style="transform-origin: 168px 110px">
                <circle cx="168" cy="84" r="22" fill="#fbbf24" stroke="#92400e" stroke-width="2.5"/>
                <circle cx="168" cy="84" r="10" fill="#fff" stroke="#92400e" stroke-width="2"/>
                <circle cx="160" cy="68" r="2.4" fill="#111827"/><circle cx="176" cy="68" r="2.4" fill="#111827"/>
                <path d="M162 100 Q168 105 174 100" fill="none" stroke="#111827" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M160 106 v10 M176 106 v10" stroke="#92400e" stroke-width="3" stroke-linecap="round"/>
            </g>
        </svg>

        {{-- 6. Ular hijau melata: badan lurus–melengkung–lurus–melengkung bergantian --}}
        <svg class="apr-char apr-char-wide" data-char="ular" viewBox="0 0 200 130" role="img" aria-label="Ular hijau melata">
            <ellipse cx="96" cy="116" rx="84" ry="4" fill="#000" opacity=".1"/>
            <path class="apr-snake-body" d="M14 104 H44 Q59 80 74 104 H104 Q119 80 134 104 H160" fill="none" stroke="#166534" stroke-width="17" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="apr-snake-body" d="M14 104 H44 Q59 80 74 104 H104 Q119 80 134 104 H160" fill="none" stroke="#22c55e" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="apr-snake-body apr-snake-scales" d="M14 104 H44 Q59 80 74 104 H104 Q119 80 134 104 H160" fill="none" stroke="#bbf7d0" stroke-width="3" stroke-dasharray="2 9" stroke-linecap="round"/>
            <g class="apr-snake-head">
                <path class="apr-tongue" d="M190 101 h8 l4 -3 M198 101 l4 3" fill="none" stroke="#e11d48" stroke-width="1.8" stroke-linecap="round"/>
                <ellipse cx="174" cy="100" rx="18" ry="13" fill="#22c55e" stroke="#166534" stroke-width="2.5"/>
                <ellipse cx="176" cy="92" rx="5" ry="5.5" fill="#fff" stroke="#111827" stroke-width="1.4"/>
                <circle cx="177.5" cy="93" r="2.6" fill="#111827"/>
                <path d="M178 106 Q184 109 189 104" fill="none" stroke="#111827" stroke-width="1.6" stroke-linecap="round"/>
                <ellipse cx="168" cy="105" rx="3.5" ry="2" fill="#fb7185" opacity=".7"/>
            </g>
        </svg>
    </div>
</div>

<style>
    #apresiasi { position: fixed; inset: auto 0 0 0; height: 0; z-index: 60; pointer-events: none; }
    #apresiasi .apr-walker { position: absolute; left: 0; bottom: 8px; display: flex; flex-direction: column; align-items: flex-start; pointer-events: auto; cursor: pointer;
        transform: translateX(-240px); animation: apr-walk 22s linear forwards; }
    #apresiasi .apr-char { display: none; width: 104px; height: 108px; transform-origin: 50% 100%; filter: drop-shadow(0 6px 6px rgba(0,0,0,.18)); }
    #apresiasi .apr-char-wide { width: 168px; }
    #apresiasi .apr-char.is-on { display: block; }
    #apresiasi [data-char="bintang"], #apresiasi [data-char="printer"] { animation: apr-dance .5s ease-in-out infinite alternate; }
    #apresiasi .apr-leg-l { animation: apr-step .5s ease-in-out infinite alternate; transform-box: fill-box; transform-origin: 50% 0; }
    #apresiasi .apr-leg-r { animation: apr-step .5s ease-in-out infinite alternate-reverse; transform-box: fill-box; transform-origin: 50% 0; }
    #apresiasi .apr-arm { animation: apr-wave .35s ease-in-out infinite alternate; transform-origin: 108px 48px; }
    #apresiasi .apr-drop { animation: apr-hop-drop .6s ease-in-out infinite alternate; animation-delay: calc(var(--i) * -.15s); }
    #apresiasi .apr-wheel { animation: apr-roll 1.4s linear infinite; }
    #apresiasi .apr-flag { animation: apr-flap .7s ease-in-out infinite alternate; transform-origin: 108px 78px; }
    #apresiasi .apr-paper { animation: apr-print .9s ease-in-out infinite alternate; }
    #apresiasi .apr-blink { animation: apr-blink 3s steps(1) infinite; transform-box: fill-box; transform-origin: 50% 50%; }
    #apresiasi .apr-blade-a { animation: apr-snip-a .3s ease-in-out infinite alternate; }
    #apresiasi .apr-blade-b { animation: apr-snip-b .3s ease-in-out infinite alternate; }
    #apresiasi .apr-hop { animation: apr-hop-drop .45s ease-in-out infinite alternate; }
    #apresiasi .apr-snake-body { animation: apr-slither .6s ease-in-out infinite alternate; }
    #apresiasi .apr-snake-head { animation: apr-snake-nod .6s ease-in-out infinite alternate; }
    #apresiasi .apr-tongue { animation: apr-tongue .5s steps(1) infinite; }
    #apresiasi .apr-bubble { max-width: min(520px, 85vw); margin: 0 0 6px 40px; padding: 9px 12px; background: #fff; color: #111827; border: 2px solid #111827; border-radius: 14px;
        font-size: 13px; line-height: 1.35; box-shadow: 3px 3px 0 #06b6d4; position: relative; }
    #apresiasi .apr-bubble::after { content: ""; position: absolute; left: 22px; bottom: -9px; width: 14px; height: 14px; background: #fff; border-right: 2px solid #111827; border-bottom: 2px solid #111827; transform: rotate(45deg); }
    #apresiasi .apr-stats { margin-top: 3px; font-size: 12px; color: #be185d; font-weight: 600; }
    @keyframes apr-walk { 0% { transform: translateX(-240px); } 100% { transform: translateX(calc(100vw + 40px)); } }
    @keyframes apr-dance { from { transform: rotate(-7deg) translateY(0); } to { transform: rotate(7deg) translateY(-8px); } }
    @keyframes apr-step { from { transform: rotate(-14deg); } to { transform: rotate(14deg); } }
    @keyframes apr-wave { from { transform: rotate(-20deg); } to { transform: rotate(25deg); } }
    @keyframes apr-hop-drop { from { transform: translateY(0); } to { transform: translateY(-12px); } }
    @keyframes apr-roll { to { transform: rotate(360deg); } }
    @keyframes apr-flap { from { transform: skewY(-3deg); } to { transform: skewY(3deg); } }
    @keyframes apr-print { from { transform: translateY(6px); } to { transform: translateY(-4px); } }
    @keyframes apr-blink { 0%, 92% { transform: scaleY(1); } 93%, 100% { transform: scaleY(.1); } }
    @keyframes apr-snip-a { from { transform: rotate(0deg); } to { transform: rotate(14deg); } }
    @keyframes apr-snip-b { from { transform: rotate(0deg); } to { transform: rotate(-14deg); } }
    /* Bagian lurus dan lengkung bertukar tempat, seolah ular melata. */
    @keyframes apr-slither {
        from { d: path("M14 104 H44 Q59 80 74 104 H104 Q119 80 134 104 H160"); }
        to { d: path("M14 104 Q29 80 44 104 H74 Q89 80 104 104 H134 Q147 92 160 104"); }
    }
    @keyframes apr-tongue { 0%, 60% { opacity: 1; } 61%, 100% { opacity: 0; } }
    @keyframes apr-snake-nod { from { transform: translateY(0); } to { transform: translateY(-3px); } }
    @media (prefers-reduced-motion: reduce) {
        #apresiasi .apr-walker { animation: none; transform: translateX(16px); }
        #apresiasi .apr-walker * { animation: none !important; }
    }
    @media print { #apresiasi { display: none !important; } }
</style>

<script>
    (() => {
        const root = document.getElementById('apresiasi');
        if (!root) return;
        const url = @js(route('apresiasi'));
        const storeKey = 'spektrum-apresiasi-terakhir';
        const charKey = 'spektrum-apresiasi-karakter';
        const read = (key) => { try { return localStorage.getItem(key); } catch { return null; } };
        const write = (key, value) => { try { localStorage.setItem(key, value); } catch {} };
        const fmt = (n) => Number(n).toLocaleString('id-ID');
        const chars = [...root.querySelectorAll('.apr-char')];

        // Karakter acak, tidak sama dengan yang tampil terakhir.
        const pickChar = (wanted) => {
            const last = read(charKey);
            const pool = chars.filter((c) => c.dataset.char !== last);
            const chosen = chars.find((c) => c.dataset.char === wanted) || pool[Math.floor(Math.random() * pool.length)] || chars[0];
            write(charKey, chosen.dataset.char);
            chars.forEach((c) => c.classList.toggle('is-on', c === chosen));
        };

        // Jangan ganggu: sedang mengetik, ada dialog/modal terbuka, atau tab tidak terlihat.
        const busy = () => {
            const el = document.activeElement;
            if (el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName))) return true;
            if (document.hidden) return true;
            return [...document.querySelectorAll('dialog[open], [aria-modal="true"], [role="dialog"]')]
                .some((m) => m.offsetParent !== null || getComputedStyle(m).position === 'fixed' && getComputedStyle(m).display !== 'none');
        };

        const show = (data, wantedChar = null) => {
            pickChar(wantedChar);
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
                if (!data.aktif || read(storeKey) === data.jam) return;
                write(storeKey, data.jam); // satu kali per jam walau banyak tab terbuka
                if (data.names && data.names.length) show(data);
            } catch {}
        };

        const scheduleNext = () => {
            const now = new Date();
            const next = new Date(now); next.setHours(now.getHours() + 1, 0, 0, 0);
            // Jeda acak 5–45 detik agar tidak semua komputer meminta bersamaan.
            setTimeout(() => { attempt(); scheduleNext(); }, next - now + 5000 + Math.random() * 40000);
        };

        // Coba tampilan tanpa menunggu jam (data contoh, tidak ke server):
        //   ?maskot=coba  → karakter acak;  ?maskot=printer (bintang|tinta|roll|printer|gunting|ular) → karakter tertentu.
        const preview = new URLSearchParams(location.search).get('maskot');
        if (preview) {
            setTimeout(() => show({ names: ['Adisa', 'Zildjian', 'Sanjay', 'Rina', 'Dewi', 'Bagus', 'Lia', 'Fajar'], more: 0, progress: 124, pembayaran: 18, lembar_kerja: 9 },
                preview === 'coba' ? null : preview), 800);
        }

        // Baru buka/muat ulang halaman di 10 menit awal jam: tetap kebagian (kalau belum tampil).
        if (new Date().getMinutes() < 10) setTimeout(() => attempt(), 3000 + Math.random() * 5000);
        scheduleNext();
    })();
</script>
