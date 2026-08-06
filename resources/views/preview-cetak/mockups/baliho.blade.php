<svg data-mockup="baliho" x-show="media === 'baliho'" x-cloak viewBox="0 0 600 420" class="w-full" style="max-width: 460px; max-height: 420px;">
    <defs>
        <clipPath id="clip-baliho">
            <rect x="60" y="40" width="480" height="240" rx="4" />
        </clipPath>
        <linearGradient id="sky-baliho" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#eef2ff" />
            <stop offset="1" stop-color="#f8fafc" />
        </linearGradient>
    </defs>

    <rect x="0" y="0" width="600" height="420" fill="url(#sky-baliho)" />
    <rect x="0" y="380" width="600" height="40" fill="#d9dde3" />

    <rect x="136" y="272" width="14" height="110" fill="#9ca3af" />
    <rect x="450" y="272" width="14" height="110" fill="#9ca3af" />
    <rect x="120" y="376" width="46" height="10" fill="#6b7280" />
    <rect x="434" y="376" width="46" height="10" fill="#6b7280" />

    <rect x="60" y="40" width="480" height="240" rx="4" fill="#00000014" transform="translate(6,8)" />
    <rect x="60" y="40" width="480" height="240" rx="4" fill="#fdfdfd" stroke="#d1d5db" stroke-width="3" />

    <g clip-path="url(#clip-baliho)">
        <rect x="60" y="40" width="480" height="240" fill="#eef0f3" />
        <text x="300" y="165" text-anchor="middle" font-size="14" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="60" y="40" width="480" height="240" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
    </g>

    <rect x="60" y="40" width="480" height="240" rx="4" fill="none" stroke="#9ca3af" stroke-width="2" />
</svg>
