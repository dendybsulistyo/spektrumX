@props(['date'])

@php
    $orderDate = \Carbon\Carbon::parse($date);
    $months = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES',
    ];
@endphp

<aside class="order-date-rail" title="Tanggal order: {{ $orderDate->format('d-m-Y') }}" aria-label="Tanggal order {{ $orderDate->format('d-m-Y') }}">
    <strong>{{ $orderDate->format('d') }}</strong>
    <span>{{ $months[(int) $orderDate->format('n')] }}</span>
    <small>{{ $orderDate->format('y') }}</small>
</aside>
