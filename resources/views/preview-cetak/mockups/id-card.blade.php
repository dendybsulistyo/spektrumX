<svg data-mockup="id-card" x-show="media === 'id-card'" x-cloak viewBox="0 0 300 460" class="w-full" style="max-width: 260px; max-height: 440px;">
    <defs>
        <clipPath id="clip-id-card">
            <rect x="40" y="100" width="220" height="340" rx="14" />
        </clipPath>
    </defs>

    <path d="M120,0 L110,90 L190,90 L180,0" fill="#e5e7eb" stroke="#d1d5db" stroke-width="1.5" />
    <path d="M115,0 L185,0 L182,10 L118,10 Z" fill="#9ca3af" />

    <rect x="40" y="100" width="220" height="340" rx="14" fill="#00000012" transform="translate(4,6)" />
    <rect x="40" y="100" width="220" height="340" rx="14" fill="#fdfdfd" stroke="#d1d5db" stroke-width="2" />

    <circle cx="150" cy="122" r="7" fill="none" stroke="#d1d5db" stroke-width="2" />

    <g clip-path="url(#clip-id-card)">
        <rect x="55" y="145" width="190" height="270" fill="#eef0f3" />
        <text x="150" y="285" text-anchor="middle" font-size="12" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="55" y="145" width="190" height="270" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
    </g>
</svg>
