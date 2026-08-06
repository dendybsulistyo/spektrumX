<svg data-mockup="kaos" x-show="media === 'kaos'" x-cloak viewBox="0 0 400 480" class="w-full" style="max-width: 320px; max-height: 420px;">
    <defs>
        <clipPath id="clip-kaos">
            <rect x="150" y="175" width="100" height="130" rx="4" />
        </clipPath>
        <linearGradient id="shade-kaos" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000000" stop-opacity="0.06" />
            <stop offset="0.5" stop-color="#000000" stop-opacity="0" />
            <stop offset="1" stop-color="#000000" stop-opacity="0.1" />
        </linearGradient>
    </defs>

    <path d="M140,70 Q200,102 260,70 L340,130 L300,162 L300,432 Q300,452 280,452 L120,452 Q100,452 100,432 L100,162 L60,130 Z"
          fill="#fdfdfd" stroke="#d1d5db" stroke-width="2" />

    <g clip-path="url(#clip-kaos)">
        <rect x="150" y="175" width="100" height="130" fill="#eef0f3" />
        <text x="200" y="245" text-anchor="middle" font-size="11" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="150" y="175" width="100" height="130" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
        <rect x="150" y="175" width="100" height="130" fill="url(#shade-kaos)" />
    </g>

    <path d="M140,70 Q200,102 260,70 L250,90 Q200,116 150,90 Z" fill="#fdfdfd" stroke="#d1d5db" stroke-width="1.5" />
</svg>
