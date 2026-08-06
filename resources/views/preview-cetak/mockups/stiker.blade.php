<svg data-mockup="stiker" x-show="media === 'stiker'" x-cloak viewBox="0 0 400 400" class="w-full" style="max-width: 320px; max-height: 420px;">
    <defs>
        <clipPath id="clip-stiker">
            <path d="M100,116 L284,116 Q300,116 300,132 L300,284 Q300,300 284,300 L116,300 Q100,300 100,284 Z" />
        </clipPath>
    </defs>

    <rect x="40" y="40" width="320" height="320" rx="12" fill="#f3f4f6" />

    <path d="M104,120 L284,120 Q296,120 296,132 L296,284 Q296,296 284,296 L116,296 Q104,296 104,284 Z"
          fill="#00000014" transform="translate(6,8)" />

    <g clip-path="url(#clip-stiker)">
        <rect x="100" y="116" width="200" height="184" fill="#eef0f3" />
        <text x="200" y="212" text-anchor="middle" font-size="11" fill="#9ca3af" x-show="!designUrl">Area Cetak</text>
        <image :href="designUrl" x="100" y="116" width="200" height="184" preserveAspectRatio="xMidYMid slice" x-show="designUrl" />
    </g>

    <path d="M100,116 L284,116 Q300,116 300,132 L300,284 Q300,300 284,300 L116,300 Q100,300 100,284 Z"
          fill="none" stroke="#d1d5db" stroke-width="1.5" />

    <path d="M264,116 L300,116 L300,152 Q264,152 264,116 Z" fill="#fdfdfd" stroke="#d1d5db" stroke-width="1.5" />
</svg>
