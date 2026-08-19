<svg data-mockup="mug" x-show="media === 'mug'" x-cloak viewBox="0 0 400 400" class="w-full" style="max-width: 320px; max-height: 420px;">
    <defs>
        <clipPath id="clip-mug">
            <rect x="140" y="140" width="120" height="140" rx="6" />
        </clipPath>
        <linearGradient id="shade-mug" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#000000" stop-opacity="0.35" />
            <stop offset="0.18" stop-color="#000000" stop-opacity="0" />
            <stop offset="0.82" stop-color="#000000" stop-opacity="0" />
            <stop offset="1" stop-color="#000000" stop-opacity="0.35" />
        </linearGradient>
        <linearGradient id="gloss-mug" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.35" />
            <stop offset="0.3" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <ellipse cx="200" cy="122" rx="90" ry="16" fill="#f3f4f6" stroke="#d1d5db" stroke-width="2" />
    <rect x="110" y="122" width="180" height="200" rx="10" fill="#fdfdfd" stroke="#d1d5db" stroke-width="2" />
    <ellipse cx="200" cy="322" rx="90" ry="14" fill="#eceef1" stroke="#d1d5db" stroke-width="2" />
    <path d="M285,160 Q345,160 345,215 Q345,270 285,270" fill="none" stroke="#d1d5db" stroke-width="16" stroke-linecap="round" />
    <path d="M285,160 Q345,160 345,215 Q345,270 285,270" fill="none" stroke="#fdfdfd" stroke-width="10" stroke-linecap="round" />

    <g clip-path="url(#clip-mug)">
        <rect x="140" y="140" width="120" height="140" fill="#eef0f3" />
        <text x="200" y="215" text-anchor="middle" font-size="11" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="140" y="140" width="120" height="140" :preserveAspectRatio="imageFit" x-show="designUrl" />
        <rect x="140" y="140" width="120" height="140" fill="url(#shade-mug)" />
        <rect x="140" y="140" width="120" height="140" fill="url(#gloss-mug)" />
    </g>

    <ellipse cx="200" cy="122" rx="90" ry="16" fill="none" stroke="#d1d5db" stroke-width="2" />
</svg>
