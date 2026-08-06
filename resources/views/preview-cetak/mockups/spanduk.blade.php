<svg data-mockup="spanduk" x-show="media === 'spanduk'" x-cloak viewBox="0 0 600 320" class="w-full" style="max-width: 460px; max-height: 420px;">
    <defs>
        <clipPath id="clip-spanduk">
            <rect x="60" y="70" width="480" height="180" rx="4" />
        </clipPath>
    </defs>

    <line x1="30" y1="0" x2="30" y2="300" stroke="#c4c9d1" stroke-width="8" />
    <line x1="570" y1="0" x2="570" y2="300" stroke="#c4c9d1" stroke-width="8" />
    <rect x="0" y="296" width="600" height="8" fill="#c4c9d1" />

    <line x1="30" y1="70" x2="70" y2="80" stroke="#9ca3af" stroke-width="2" />
    <line x1="570" y1="70" x2="530" y2="80" stroke="#9ca3af" stroke-width="2" />
    <line x1="30" y1="250" x2="70" y2="240" stroke="#9ca3af" stroke-width="2" />
    <line x1="570" y1="250" x2="530" y2="240" stroke="#9ca3af" stroke-width="2" />

    <rect x="60" y="70" width="480" height="180" rx="4" fill="#00000012" transform="translate(5,7)" />
    <rect x="60" y="70" width="480" height="180" rx="4" fill="#fdfdfd" stroke="#d1d5db" stroke-width="2" />

    <g clip-path="url(#clip-spanduk)">
        <rect x="60" y="70" width="480" height="180" fill="#eef0f3" />
        <text x="300" y="165" text-anchor="middle" font-size="13" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="60" y="70" width="480" height="180" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
    </g>

    <circle cx="72" cy="82" r="6" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1.5" />
    <circle cx="528" cy="82" r="6" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1.5" />
    <circle cx="72" cy="238" r="6" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1.5" />
    <circle cx="528" cy="238" r="6" fill="#e5e7eb" stroke="#9ca3af" stroke-width="1.5" />
</svg>
