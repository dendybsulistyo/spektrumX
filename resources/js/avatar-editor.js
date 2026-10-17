/**
 * Editor avatar mini (gaya Lorelei + hijab buatan sendiri).
 * Dimuat HANYA di halaman Profil lewat @vite — halaman lain cukup memakai
 * gambar SVG yang sudah disimpan di server.
 */
import { createAvatar } from '@dicebear/core';
import * as lorelei from '@dicebear/lorelei';

const pad = (n) => String(n).padStart(2, '0');

export const AVATAR_OPTIONS = {
    hairPria: ['variant01', 'variant02', 'variant03', 'variant04', 'variant06', 'variant08', 'variant09', 'variant28', 'variant39', 'variant47'],
    hairWanita: ['variant13', 'variant15', 'variant16', 'variant19', 'variant21', 'variant23', 'variant24', 'variant32', 'variant35', 'variant40'],
    hairHijab: 'variant25',
    eyes: Array.from({ length: 12 }, (_, i) => 'variant' + pad(i + 1)),
    eyebrows: Array.from({ length: 6 }, (_, i) => 'variant' + pad(i + 1)),
    mouth: Array.from({ length: 10 }, (_, i) => 'happy' + pad(i + 1)),
    nose: Array.from({ length: 6 }, (_, i) => 'variant' + pad(i + 1)),
    hijabColors: ['#1b2236', '#0f8fb3', '#c8246c', '#2e8b57', '#b07d00', '#8a6fb8', '#9a948a', '#5b3a29'],
    backgrounds: ['dfe4f0', 'd6eef6', 'f6dbe6', 'd9efe1', 'faedc0', 'e5ddf6'],
    hats: ['none', 'pet', 'kupluk', 'peci'],
    hatLabels: { none: 'Tidak', pet: 'Pet', kupluk: 'Kupluk', peci: 'Peci' },
    hatColors: ['#1b2236', '#c8246c', '#0f8fb3', '#2e8b57', '#b07d00', '#d9480f', '#9a948a', '#f4f1ea'],
};

const pick = (list) => list[Math.floor(Math.random() * list.length)];

const hijabLayer = (color) => {
    const outer = 'M70 990 C95 900 170 850 250 820 C215 760 200 690 205 610 C200 520 210 430 245 345 C290 240 390 168 500 165 C615 162 712 230 755 340 C790 430 798 520 792 610 C796 690 780 760 745 820 C825 850 890 900 915 990 Z';
    const face = 'M378 360 C410 290 500 262 575 266 C660 270 722 312 745 380 C760 440 758 510 750 575 C740 660 675 735 565 768 C462 744 398 690 380 615 C366 535 364 440 378 360 Z';
    const ciput = 'M378 360 C410 290 500 262 575 266 C660 270 722 312 745 380 C742 318 706 262 645 232 C585 205 500 203 435 228 C392 248 370 298 378 360 Z';
    return `<g stroke="#000" stroke-linejoin="round" stroke-linecap="round">`
        + `<path d="${outer} ${face}" fill="${color}" fill-rule="evenodd" stroke-width="10"/>`
        + `<path d="${ciput}" fill="#ffffff" stroke-width="8"/>`
        + `<path d="M250 820 C330 845 420 860 500 872 C600 885 690 860 745 820" fill="none" stroke-width="8"/>`
        + `<path d="M745 820 C700 880 640 935 590 990" fill="none" stroke-width="8"/>`
        + `<path d="M620 885 C660 920 690 955 705 990" fill="none" stroke-width="6" opacity=".45"/>`
        + `<path d="M330 860 C310 900 300 945 300 990" fill="none" stroke-width="6" opacity=".45"/>`
        + `<path d="M232 470 C226 540 228 610 246 690" fill="none" stroke-width="6" opacity=".35"/>`
        + `<path d="M770 470 C776 540 774 610 756 690" fill="none" stroke-width="6" opacity=".35"/>`
        + `</g>`;
};

/**
 * Penyesuai posisi topi: gambar dasar dibuat agak ke kanan & terlalu rendah,
 * jadi semua titik digeser ke tengah tengkorak (±x 492), dikecilkan sedikit
 * dan dimiringkan mengikuti kepala yang menoleh ke kanan (sisi kanan lebih tinggi).
 */
const HAT_FIT = { cx: 527, tx: 488, sx: 0.93, sy: 0.92, dy: -38, tilt: -0.07 };
const fitHat = (markup) => markup.replace(/(-?\d+(?:\.\d+)?) (-?\d+(?:\.\d+)?)/g, (_, x, y) => {
    const f = HAT_FIT;
    const nx = f.tx + (Number(x) - f.cx) * f.sx;
    const ny = Number(y) * f.sy + f.dy + (Number(x) - f.cx) * f.tilt;
    return `${Math.round(nx)} ${Math.round(ny)}`;
});

/** Topi buatan sendiri (Lorelei tidak punya topi); digambar di atas rambut. */
const hatLayer = (type, color) => fitHat(hatShape(type, color));

const hatShape = (type, color) => {
    const g = (inner) => `<g stroke="#000" stroke-linejoin="round" stroke-linecap="round">${inner}</g>`;
    if (type === 'pet') {
        return g(
            `<path d="M268 372 C255 250 330 140 470 118 C610 98 735 165 778 292 C786 318 788 340 786 360 C700 330 560 318 420 330 C360 336 310 350 268 372 Z" fill="${color}" stroke-width="10"/>`
            + `<path d="M560 322 C650 300 760 300 850 318 C900 328 925 352 905 372 C860 388 780 372 700 362 C640 354 590 350 560 346 Z" fill="${color}" stroke-width="10"/>`
            + `<path d="M520 112 C505 180 498 255 500 326" fill="none" stroke-width="7" opacity=".55"/>`
            + `<path d="M395 140 C360 205 340 270 336 342" fill="none" stroke-width="6" opacity=".35"/>`
            + `<path d="M650 140 C690 200 712 262 720 330" fill="none" stroke-width="6" opacity=".35"/>`
            + `<path d="M500 122 C496 104 540 100 538 120 Z" fill="${color}" stroke-width="8"/>`
        );
    }
    if (type === 'kupluk') {
        return g(
            `<path d="M272 335 C262 220 330 128 470 104 C610 84 742 150 780 270 C788 295 790 318 788 335 Z" fill="${color}" stroke-width="10"/>`
            + `<path d="M258 318 C420 290 620 285 800 312 L806 392 C620 362 420 368 262 400 Z" fill="${color}" stroke-width="10"/>`
            + `<path d="M330 312 L328 384 M400 302 L398 374 M470 298 L470 368 M540 296 L541 366 M610 298 L612 368 M680 302 L683 374 M745 308 L749 382" fill="none" stroke-width="6" opacity=".35"/>`
            + `<path d="M520 104 C488 92 482 52 518 44 C556 38 562 88 520 104 Z" fill="${color}" stroke-width="9"/>`
        );
    }
    if (type === 'peci') {
        return g(
            `<path d="M262 395 L292 128 C430 104 640 104 768 126 L792 380 C630 350 420 356 262 395 Z" fill="${color}" stroke-width="10"/>`
            + `<path d="M292 128 C430 150 640 150 768 126" fill="none" stroke-width="7" opacity=".45"/>`
            + `<path d="M272 318 C430 288 630 284 786 306" fill="none" stroke-width="5" opacity=".25"/>`
        );
    }
    return '';
};

/** Pilihan acak sesuai jenis (Pria / Wanita / Wanita berhijab). */
export function randomAvatar(gender = 'pria', hijab = false, keep = {}) {
    const o = AVATAR_OPTIONS;
    return {
        gender,
        hijab: gender === 'wanita' && hijab,
        hair: gender === 'pria' ? pick(o.hairPria) : pick(o.hairWanita),
        eyes: pick(o.eyes),
        eyebrows: pick(o.eyebrows),
        mouth: pick(o.mouth),
        nose: pick(o.nose),
        glasses: Math.random() < 0.2,
        beard: gender === 'pria' && Math.random() < 0.2,
        hijabColor: keep.hijabColor ?? pick(o.hijabColors),
        hat: keep.hat ?? 'none',
        hatColor: keep.hatColor ?? pick(o.hatColors),
        background: keep.background ?? pick(o.backgrounds),
    };
}

/** Bentuk SVG avatar dari pilihan. Metadata dibuang agar ringkas. */
export function renderAvatar(a) {
    const hijab = a.gender === 'wanita' && a.hijab;
    const svg = createAvatar(lorelei, {
        seed: 'spektrum',
        hair: [hijab ? AVATAR_OPTIONS.hairHijab : a.hair],
        eyes: [a.eyes],
        eyebrows: [a.eyebrows],
        mouth: [a.mouth],
        nose: [a.nose],
        glasses: ['variant01'],
        glassesProbability: a.glasses ? 100 : 0,
        beard: ['variant01'],
        beardProbability: a.gender === 'pria' && a.beard ? 100 : 0,
        earringsProbability: 0,
        hairAccessoriesProbability: 0,
        frecklesProbability: 0,
        backgroundColor: [a.background],
    }).toString().replace(/<metadata[\s\S]*?<\/metadata>/, '');

    if (hijab) return svg.replace(/<\/svg>$/, hijabLayer(a.hijabColor) + '</svg>');
    const hat = a.hat && a.hat !== 'none' ? hatLayer(a.hat, a.hatColor || '#1b2236') : '';
    return hat ? svg.replace(/<\/svg>$/, hat + '</svg>') : svg;
}

window.SpektrumAvatar = { AVATAR_OPTIONS, randomAvatar, renderAvatar };
