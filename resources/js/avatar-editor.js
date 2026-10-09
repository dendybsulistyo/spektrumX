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

    return hijab ? svg.replace(/<\/svg>$/, hijabLayer(a.hijabColor) + '</svg>') : svg;
}

window.SpektrumAvatar = { AVATAR_OPTIONS, randomAvatar, renderAvatar };
