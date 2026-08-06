<svg data-mockup="kartu-nama" x-show="media === 'kartu-nama'" x-cloak viewBox="0 0 400 300" class="w-full" style="max-width: 360px; max-height: 420px;">
    <defs>
        <clipPath id="clip-kartu-nama">
            <rect x="0" y="0" width="360" height="220" rx="14" />
        </clipPath>
        <linearGradient id="gloss-kartu" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.25" />
            <stop offset="0.4" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <rect x="20" y="220" width="360" height="20" rx="10" fill="#00000010" />

    <g transform="translate(20,40) skewY(-4)">
        <rect x="4" y="6" width="360" height="220" rx="14" fill="#00000018" />
        <g clip-path="url(#clip-kartu-nama)">
            <rect x="0" y="0" width="360" height="220" fill="#eef0f3" />
            <text x="180" y="115" text-anchor="middle" font-size="13" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
            <image :href="designUrl" x="0" y="0" width="360" height="220" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
            <rect x="0" y="0" width="360" height="220" fill="url(#gloss-kartu)" />
        </g>
        <rect x="0" y="0" width="360" height="220" rx="14" fill="none" stroke="#d1d5db" stroke-width="1.5" />
    </g>
</svg>
