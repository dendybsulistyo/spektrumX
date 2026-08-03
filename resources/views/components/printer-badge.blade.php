@props(['code' => null, 'name' => null])

@php
    // Fixed mapping for the printers this palette was originally designed
    // around — kept explicit so their color never shifts as new printers
    // get added elsewhere in this list.
    $knownColors = [
        '01' => 'bg-amber-100 text-amber-800',
        '02' => 'bg-teal-100 text-teal-800',
        '03' => 'bg-violet-100 text-violet-800',
        '04' => 'bg-rose-100 text-rose-800',
        '05' => 'bg-sky-100 text-sky-800',
    ];

    // Any printer added later (KdPrn not in the map above) still gets a
    // distinct color automatically, picked deterministically from this pool
    // by hashing its code — same printer always lands on the same color,
    // no code change needed when Data Printer gets a new entry.
    $fallbackPool = [
        'bg-lime-100 text-lime-800',
        'bg-cyan-100 text-cyan-800',
        'bg-fuchsia-100 text-fuchsia-800',
        'bg-orange-100 text-orange-800',
        'bg-indigo-100 text-indigo-800',
        'bg-emerald-100 text-emerald-800',
        'bg-pink-100 text-pink-800',
        'bg-blue-100 text-blue-800',
    ];

    $classes = $knownColors[$code]
        ?? ($code ? $fallbackPool[crc32($code) % count($fallbackPool)] : 'bg-gray-100 text-gray-600');
@endphp

@if ($name)
    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold whitespace-nowrap {{ $classes }}">
        {{ $name }}
    </span>
@else
    <span class="text-gray-400">-</span>
@endif
