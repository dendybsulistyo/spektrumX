<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SpektrumX') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @php
            $navIcon = function (string $name) {
                $icons = [
                    'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
                    'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />',
                    'cube' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
                    'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />',
                    'user-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />',
                    'printer' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />',
                    'archive' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
                    'banknotes' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />',
                    'document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
                    'truck' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.457c.9 0 1.65.673 1.763 1.565l.318 2.55m-7.538-4.116V6.75m0 0V4.875c0-.621-.504-1.125-1.125-1.125H10.5m3.75 3H14.25" />',
                    'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
                    'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
                    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
                ];
                return $icons[$name] ?? $icons['tag'];
            };
        @endphp
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

            <!-- Mobile overlay -->
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                 class="fixed inset-0 bg-gray-900/50 z-30 lg:hidden"></div>

            <!-- Sidebar -->
            <aside
                 class="fixed left-0 top-0 h-screen w-60 bg-white border-r border-gray-200 flex flex-col z-40 transform transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0"
                 :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="h-16 px-4 border-b border-gray-200 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-md bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">S</div>
                        <div class="min-w-0">
                            <h1 class="text-sm font-bold text-gray-900 leading-none truncate">{{ config('app.name', 'SpektrumX') }}</h1>
                            <p class="text-[10px] text-gray-400 mt-0.5">Admin Percetakan</p>
                        </div>
                    </div>
                    <button @click="sidebarOpen = false" class="lg:hidden shrink-0 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @php
                    $navClass = fn (bool $active) => 'flex items-center gap-2.5 px-2.5 py-2 rounded-lg font-semibold transition '
                        .($active ? 'bg-indigo-50 text-indigo-600' : 'text-gray-800 hover:bg-gray-100');
                    $iconClass = fn (bool $active) => 'w-[18px] h-[18px] shrink-0 '.($active ? 'text-indigo-600' : 'text-gray-400');
                @endphp

                <nav class="flex-1 px-2.5 py-3 space-y-0.5 overflow-y-auto text-[13px]" @click="sidebarOpen = false">
                    @php $active = request()->routeIs('dashboard'); @endphp
                    <a href="{{ route('dashboard') }}" class="{{ $navClass($active) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('dashboard') !!}</svg>
                        Dashboard
                    </a>

                    @if (Auth::user()->hasPermission('customers.view') || Auth::user()->hasPermission('produk.view') || Auth::user()->hasPermission('harga-artwork.view') || Auth::user()->hasPermission('operators.view') || Auth::user()->hasPermission('printers.view') || Auth::user()->hasPermission('bahan-outdoor.view') || Auth::user()->hasPermission('kategori-bahan-outdoor.view') || Auth::user()->hasPermission('harga-cetak-outdoor.view'))
                        <p class="px-2.5 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Master Data</p>
                    @endif

                    @can('customers.view')
                        @php $active = request()->routeIs('customers.*'); @endphp
                        <a href="{{ route('customers.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('users') !!}</svg>
                            Customer
                        </a>
                    @endcan

                    @can('produk.view')
                        @php $active = request()->routeIs('produk.*'); @endphp
                        <a href="{{ route('produk.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('cube') !!}</svg>
                            Produk Indoor
                        </a>
                    @endcan

                    @can('produk.view')
                        @php $active = request()->routeIs('detail-indoor.*'); @endphp
                        <a href="{{ route('detail-indoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('tag') !!}</svg>
                            Harga Indoor
                        </a>
                    @endcan

                    {{-- @can('kategori.view')
                        @php $active = request()->routeIs('kategori.*'); @endphp
                        <a href="{{ route('kategori.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('tag') !!}</svg>
                            Harga Indoor
                        </a>
                    @endcan --}}

                    @can('harga-artwork.view')
                        @php $active = request()->routeIs('harga-artwork.*'); @endphp
                        <a href="{{ route('harga-artwork.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('cube') !!}</svg>
                            Harga Artwork
                        </a>
                    @endcan

                    @can('harga-cetak-outdoor.view')
                        @php $active = request()->routeIs('harga-cetak-outdoor.*'); @endphp
                        <a href="{{ route('harga-cetak-outdoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('banknotes') !!}</svg>
                            Harga Outdoor
                        </a>
                    @endcan


                    @can('bahan-outdoor.view')
                        @php $active = request()->routeIs('bahan-outdoor.*'); @endphp
                        <a href="{{ route('bahan-outdoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('archive') !!}</svg>
                            Bahan Outdoor
                        </a>
                    @endcan

                    @can('kategori-bahan-outdoor.view')
                        @php $active = request()->routeIs('kategori-bahan-outdoor.*'); @endphp
                        <a href="{{ route('kategori-bahan-outdoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('tag') !!}</svg>
                            Kategori Outdoor
                        </a>
                    @endcan

                     @can('operators.view')
                        @php $active = request()->routeIs('operators.*'); @endphp
                        <a href="{{ route('operators.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('user-circle') !!}</svg>
                            Operator
                        </a>
                    @endcan

                    @can('printers.view')
                        @php $active = request()->routeIs('printers.*'); @endphp
                        <a href="{{ route('printers.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('printer') !!}</svg>
                            Printer
                        </a>
                    @endcan

                    @if (Auth::user()->hasPermission('order-indoor.view') || Auth::user()->hasPermission('order-outdoor.view'))
                        <p class="px-2.5 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Transaksi</p>
                    @endif

                    @can('order-indoor.view')
                        @php $active = request()->routeIs('order-indoor.*'); @endphp
                        <a href="{{ route('order-indoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('document') !!}</svg>
                            Order Indoor
                        </a>
                    @endcan

                    @can('order-outdoor.view')
                        @php $active = request()->routeIs('order-outdoor.*'); @endphp
                        <a href="{{ route('order-outdoor.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('truck') !!}</svg>
                            Order Outdoor
                        </a>
                    @endcan


                    @if (Auth::user()->hasPermission('kasir.view') || Auth::user()->hasPermission('order-desain.view') || Auth::user()->hasPermission('order-cetak.view') || Auth::user()->hasPermission('order-qc.view') || Auth::user()->hasPermission('pengambilan.view'))
                        <p class="px-2.5 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Dashboard Operator</p>
                    @endif

                    @can('kasir.view')
                        @php $active = request()->routeIs('kasir.*'); @endphp
                        <a href="{{ route('kasir.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('banknotes') !!}</svg>
                            Kasir
                        </a>
                    @endcan

                    @can('order-desain.view')
                        @php $active = request()->routeIs('order-desain.*'); @endphp
                        <a href="{{ route('order-desain.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('document') !!}</svg>
                            Antrian Desain
                        </a>
                    @endcan

                    @can('order-cetak.view')
                        @php $active = request()->routeIs('order-cetak.*'); @endphp
                        <a href="{{ route('order-cetak.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('printer') !!}</svg>
                            Antrian Cetak
                        </a>
                    @endcan

                    @can('order-qc.view')
                        @php $active = request()->routeIs('order-qc.*'); @endphp
                        <a href="{{ route('order-qc.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('shield') !!}</svg>
                            Antrian QC
                        </a>
                    @endcan

                    @can('pengambilan.view')
                        @php $active = request()->routeIs('pengambilan.*'); @endphp
                        <a href="{{ route('pengambilan.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('archive') !!}</svg>
                            Pengambilan Barang
                        </a>
                    @endcan

                    @can('data-warehouse.view')
                        <p class="px-2.5 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Analitik</p>

                        @php $active = request()->routeIs('data-warehouse.*'); @endphp
                        <a href="{{ route('data-warehouse.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('chart-bar') !!}</svg>
                            Data Warehouse
                        </a>
                    @endcan

                    @if (Auth::user()->hasPermission('roles.manage') || Auth::user()->hasPermission('jasa-potong.manage'))
                        <p class="px-2.5 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Pengaturan</p>
                    @endif

                    @can('roles.manage')
                        @php $active = request()->routeIs('roles.*'); @endphp
                        <a href="{{ route('roles.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('shield') !!}</svg>
                            Role & Akses
                        </a>

                        @php $active = request()->routeIs('users.*'); @endphp
                        <a href="{{ route('users.index') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('user') !!}</svg>
                            User
                        </a>
                    @endcan

                    @can('jasa-potong.manage')
                        @php $active = request()->routeIs('jasa-potong.*'); @endphp
                        <a href="{{ route('jasa-potong.edit') }}" class="{{ $navClass($active) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $iconClass($active) }}">{!! $navIcon('banknotes') !!}</svg>
                            Jasa Potong
                        </a>
                    @endcan
                </nav>
            </aside>

            <!-- Main content -->
            <div class="flex-1 min-w-0 lg:ml-60">
                <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 gap-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <button @click="sidebarOpen = true" class="lg:hidden shrink-0 text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="relative shrink-0">
                        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                {{ collect(explode(' ', Auth::user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                            </div>
                            <div class="text-left leading-tight hidden sm:block">
                                <div class="text-[13px] font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                <div class="text-[11px] text-gray-400">{{ Auth::user()->role?->label ?? 'Belum ada role' }}</div>
                            </div>
                        </button>

                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-[13px] z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-gray-600 hover:bg-gray-50">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 text-gray-600 hover:bg-gray-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="p-4 sm:p-6">
                    @if (session('status'))
                        <div class="mb-4 rounded-md bg-green-50 text-green-700 px-4 py-3 text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-3 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('partials.chat-widget')
    </body>
</html>
