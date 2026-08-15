<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SpektrumX') }}</title>

        {{-- Self-hosted (public/fonts) — app runs on client machines without
             internet access, so Google Fonts/bunny.net CDN links won't load. --}}
        <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')

        {{-- Navbar + semua halaman Tailwind yang belum ditulis ulang manual ke
             markup Industry di-reskin dari sini juga (bareng dengan
             public/_ds/industry-.../styles.css yang menangani halaman yang
             SUDAH ditulis ulang manual) — palet indigo tegas, font Figtree
             (self-hosted, lihat komentar di atas), radius kecil (maks 4px,
             kecuali .rounded-full — avatar/badge bulat dibiarkan bulat, tidak
             ikut dikotakkan). Di-scope ke .industry-nav / body.font-sans
             lewat !important supaya konsisten tanpa perlu tulis ulang tiap
             file satu-satu. --}}
        <style>
            .industry-nav {
                background: #ffffff !important;
                border-bottom: 1px solid #e3e5ee !important;
                font-family: 'Figtree', system-ui, sans-serif;
                box-shadow: 0 1px 2px color-mix(in srgb, #16181d 6%, transparent) !important;
            }
            .industry-nav [class*="rounded"]:not([class*="rounded-full"]) { border-radius: 4px !important; }
            .industry-nav .brand-mark { background: #4f46e5 !important; border-radius: 4px !important; }
            .industry-nav a, .industry-nav button, .industry-nav span, .industry-nav div {
                font-family: 'Figtree', system-ui, sans-serif;
            }
            .industry-nav input { font-family: 'Figtree', system-ui, sans-serif; }
            .industry-nav .bg-indigo-50 { background: #eef2ff !important; }
            .industry-nav .bg-indigo-100 { background: #eef2ff !important; }
            .industry-nav .bg-indigo-600 { background: #4f46e5 !important; }
            .industry-nav .text-indigo-600,
            .industry-nav .text-indigo-700 { color: #4338ca !important; }
            .industry-nav .border-gray-200 { border-color: #e3e5ee !important; }
            .industry-nav .hover\:bg-gray-100:hover { background: #f2f3f9 !important; }
            .industry-nav .shadow-lg { box-shadow: 0 12px 32px color-mix(in srgb, #16181d 18%, transparent) !important; }

            /* Reskin global untuk halaman yang belum ditulis ulang manual ke
               markup Industry (Master Data, Order Indoor/Outdoor/Artwork,
               dsb) — override class Tailwind yang sudah ada supaya ikut
               palet/font/radius Industry tanpa perlu tulis ulang tiap file
               satu-satu. Halaman yang SUDAH ditulis ulang manual (pakai
               wrapper #industry-page dsb) tidak kepakai class Tailwind lama
               ini lagi, jadi aman tidak dobel. */
            body.font-sans { font-family: 'Figtree', system-ui, sans-serif !important; background: #f4f5f8 !important; }
            body.font-sans h1, body.font-sans h2, body.font-sans h3, body.font-sans h4 { font-family: 'Figtree', system-ui, sans-serif !important; font-weight: 700 !important; }
            body.font-sans .rounded, body.font-sans .rounded-sm, body.font-sans .rounded-md,
            body.font-sans .rounded-lg, body.font-sans .rounded-xl, body.font-sans .rounded-2xl,
            body.font-sans .rounded-3xl { border-radius: 4px !important; }
            body.font-sans .bg-gray-900 { background: #1e1b4b !important; }
            body.font-sans .bg-gray-700, body.font-sans .bg-gray-800,
            body.font-sans .hover\:bg-gray-700:hover { background: #312e81 !important; }
            body.font-sans .bg-indigo-600, body.font-sans .bg-indigo-500 { background: #4f46e5 !important; }
            body.font-sans .bg-indigo-50, body.font-sans .bg-indigo-100 { background: #eef2ff !important; }
            body.font-sans .text-indigo-600, body.font-sans .text-indigo-700,
            body.font-sans .text-blue-600, body.font-sans .text-blue-700 { color: #4338ca !important; }
            body.font-sans .text-blue-800,
            body.font-sans .hover\:text-blue-800:hover { color: #312e81 !important; }
            body.font-sans .border-indigo-500,
            body.font-sans .focus\:border-indigo-500:focus { border-color: #4f46e5 !important; }
            body.font-sans .ring-indigo-500,
            body.font-sans .focus\:ring-indigo-500:focus { --tw-ring-color: #4f46e5 !important; }
            body.font-sans .border-gray-300, body.font-sans .border-gray-200 { border-color: #e3e5ee !important; }
            body.font-sans .bg-gray-50 { background: #f8f9fc !important; }
            body.font-sans .bg-gray-100, body.font-sans .hover\:bg-gray-100:hover,
            body.font-sans .hover\:bg-gray-200:hover { background: #eef0f5 !important; }
            body.font-sans .shadow-sm { box-shadow: 0 1px 2px color-mix(in srgb, #16181d 12%, transparent) !important; }
            body.font-sans .shadow-lg { box-shadow: 0 12px 32px color-mix(in srgb, #16181d 20%, transparent) !important; }

            /* Tinggi input & select disamakan — beda default browser antara
               <input> dan <select> (dan antar Tailwind size/padding yang
               tidak konsisten di berbagai form) bikin baris form kelihatan
               "loncat-loncat". Dipaksa sama di sini, satu tempat, untuk
               semua form Tailwind app ini. Elemen ber-class .input dikecualikan
               (:not(.input)) karena itu sudah diatur sendiri tingginya oleh
               design system Industry (styles.css) — tanpa pengecualian ini,
               !important di sini menang dan bikin tinggi .input beda lagi
               dari tombol .btn di sebelahnya di halaman Industry. */
            body.font-sans input[type="text"]:not(.input), body.font-sans input[type="number"]:not(.input),
            body.font-sans input[type="email"]:not(.input), body.font-sans input[type="password"]:not(.input),
            body.font-sans input[type="date"]:not(.input), body.font-sans input[type="tel"]:not(.input),
            body.font-sans input[type="search"]:not(.input), body.font-sans select:not(.input) {
                height: 2.375rem !important; padding-top: 0 !important; padding-bottom: 0 !important;
                line-height: 2.375rem !important; box-sizing: border-box !important;
            }
            body.font-sans select:not(.input) { padding-top: 0 !important; padding-bottom: 0 !important; }
        </style>

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
                    'folder' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M4.5 9.75V6a2.25 2.25 0 012.25-2.25h4.5a2.25 2.25 0 011.59.659l1.5 1.5a2.25 2.25 0 001.59.659h2.32a2.25 2.25 0 012.25 2.25v.932" />',
                    'chevron-down' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />',
                ];
                return $icons[$name] ?? $icons['tag'];
            };

            // Grouping mirrors the old sidebar sections — only the container changed
            // from a vertical accordion to a horizontal dropdown-per-group navbar.
            $masterDataActive = request()->routeIs('customers.*', 'kategori-produk-indoor.*', 'detail-indoor.*', 'harga-artwork.*', 'printer-outdoor.*', 'bahan-cetak-outdoor.*', 'harga-cetak-outdoor.*', 'printers.*');
            $transaksiActive = request()->routeIs('order-indoor.*', 'order-outdoor.*', 'order-artwork.*');
            $operatorActive = request()->routeIs('file.*', 'kasir.*', 'order-desain.*', 'order-cetak.*', 'order-finishing.*', 'order-qc.*', 'order-bungkus.*', 'pengambilan.*');
            $analitikActive = request()->routeIs('data-warehouse.*', 'monitoring-kinerja.*', 'monitoring-transaksi.*', 'papan-pantau.*');
            $keuanganActive = request()->routeIs('keuangan.*', 'pengeluaran.*', 'payroll.*', 'pembatalan.*', 'diskon-approval.*');
            $canApproveCancel = Auth::user()->hasPermission('order-indoor.approve-cancel') || Auth::user()->hasPermission('order-outdoor.approve-cancel') || Auth::user()->hasPermission('order-artwork.approve-cancel');
            $pendingApprovalCount = $canApproveCancel ? \App\Http\Controllers\PembatalanController::pendingCount() : 0;
            $canApproveDiskon = Auth::user()->hasPermission('kasir.approve-diskon');
            $pendingDiskonCount = $canApproveDiskon ? \App\Http\Controllers\DiskonApprovalController::pendingCount() : 0;
            $showKeuangan = Auth::user()->hasPermission('keuangan.view') || Auth::user()->hasPermission('pengeluaran.view') || Auth::user()->hasPermission('payroll.view') || Auth::user()->hasPermission('keuangan.pengaturan') || $canApproveCancel || $canApproveDiskon;
            $pengaturanActive = request()->routeIs('roles.*', 'users.*', 'jasa-potong.*', 'jasa-potong-artwork.*');

            $showMasterData = Auth::user()->hasPermission('customers.view') || Auth::user()->hasPermission('produk.view') || Auth::user()->hasPermission('harga-artwork.view') || Auth::user()->hasPermission('printers.view') || Auth::user()->hasPermission('printer-outdoor.view') || Auth::user()->hasPermission('bahan-cetak-outdoor.view') || Auth::user()->hasPermission('harga-cetak-outdoor.view') || Auth::user()->hasPermission('kategori-produk-indoor.view');
            $showTransaksi = Auth::user()->hasPermission('order-indoor.view') || Auth::user()->hasPermission('order-outdoor.view') || Auth::user()->hasPermission('order-artwork.view');
            $showOperator = Auth::user()->hasPermission('kasir.view') || Auth::user()->hasPermission('order-desain.view') || Auth::user()->hasPermission('order-cetak.view') || Auth::user()->hasPermission('order-finishing.view') || Auth::user()->hasPermission('order-qc.view') || Auth::user()->hasPermission('order-bungkus.view') || Auth::user()->hasPermission('pengambilan.view') || Auth::user()->hasPermission('file-monitor.view');
            $showAnalitik = Auth::user()->hasPermission('data-warehouse.view') || Auth::user()->hasPermission('monitoring-kinerja.view') || Auth::user()->hasPermission('monitoring-transaksi.view') || Auth::user()->hasPermission('papan-pantau.view');
            $showPengaturan = Auth::user()->hasPermission('roles.manage') || Auth::user()->hasPermission('jasa-potong.manage') || Auth::user()->hasPermission('jasa-potong-artwork.manage');

            $navTopLink = fn (bool $active) => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[14px] font-semibold transition '
                .($active ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900');
            $dropdownLink = fn (bool $active) => 'block px-3 py-1.5 rounded-md text-[13px] '
                .($active ? 'text-indigo-600 font-semibold bg-indigo-50' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900');
            $mobileLink = fn (bool $active) => 'block px-3 py-2 rounded-lg text-sm font-medium '
                .($active ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-100');
        @endphp
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div x-data="{ mobileMenuOpen: false }">
            <nav class="industry-nav bg-white border-b border-gray-200 sticky top-0 z-40">
                <div class="max-w-full mx-auto px-4 sm:px-6">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center min-w-0">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                                <div class="brand-mark w-7 h-7 rounded-md bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">S</div>
                                <span class="hidden sm:block text-sm font-bold text-gray-900 truncate">{{ config('app.name', 'SpektrumX') }}</span>
                            </a>

                            <div class="hidden lg:flex lg:items-center lg:ml-6 lg:gap-1">
                                @php $active = request()->routeIs('dashboard'); @endphp
                                <a href="{{ route('dashboard') }}" class="{{ $navTopLink($active) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">{!! $navIcon('dashboard') !!}</svg>
                                    Dashboard
                                </a>

                                @can('preview-cetak.view')
                                    <a href="{{ route('preview-cetak.index') }}" class="{{ $navTopLink(request()->routeIs('preview-cetak.*')) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">{!! $navIcon('printer') !!}</svg>
                                        Preview Cetak
                                    </a>
                                @endcan

                                @if ($showKeuangan)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($keuanganActive) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">{!! $navIcon('banknotes') !!}</svg>
                                            Keuangan
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            @can('keuangan.view')
                                                <a href="{{ route('keuangan.kas-harian') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.kas-harian')) }}">Kas Harian</a>
                                                <a href="{{ route('keuangan.rekap-kasir') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.rekap-kasir')) }}">Rekap per Kasir</a>
                                                <a href="{{ route('keuangan.rekap-customer') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.rekap-customer')) }}">Total Order per Customer</a>
                                                <a href="{{ route('keuangan.piutang') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.piutang')) }}">Piutang</a>
                                            @endcan
                                            @can('pengeluaran.view')
                                                <a href="{{ route('pengeluaran.index') }}" class="{{ $dropdownLink(request()->routeIs('pengeluaran.*')) }}">Pengeluaran</a>
                                            @endcan
                                            @can('payroll.view')
                                                <a href="{{ route('payroll.index') }}" class="{{ $dropdownLink(request()->routeIs('payroll.*')) }}">Payroll</a>
                                            @endcan
                                            @can('keuangan.view')
                                                <a href="{{ route('keuangan.laba-rugi') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.laba-rugi')) }}">Laba Rugi</a>
                                                <a href="{{ route('keuangan.jurnal-manual') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.jurnal-manual')) }}">Jurnal Manual</a>
                                                <a href="{{ route('keuangan.laporan-ppn') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.laporan-ppn')) }}">Laporan PPN</a>
                                                <a href="{{ route('keuangan.tutup-buku') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.tutup-buku*')) }}">Tutup Buku</a>
                                            @endcan
                                            @can('keuangan.pengaturan')
                                                <a href="{{ route('keuangan.pengaturan.edit') }}" class="{{ $dropdownLink(request()->routeIs('keuangan.pengaturan.edit')) }}">Pengaturan</a>
                                            @endcan
                                            @if ($canApproveCancel)
                                                <a href="{{ route('pembatalan.index') }}" class="{{ $dropdownLink(request()->routeIs('pembatalan.*')) }} flex items-center gap-1.5">
                                                    Approval Batal
                                                    @if ($pendingApprovalCount > 0)
                                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[11px] font-bold leading-none">{{ $pendingApprovalCount }}</span>
                                                    @endif
                                                </a>
                                            @endif
                                            @if ($canApproveDiskon)
                                                <a href="{{ route('diskon-approval.index') }}" class="{{ $dropdownLink(request()->routeIs('diskon-approval.*')) }} flex items-center gap-1.5">
                                                    Approval Diskon
                                                    @if ($pendingDiskonCount > 0)
                                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[11px] font-bold leading-none">{{ $pendingDiskonCount }}</span>
                                                    @endif
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($showMasterData)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($masterDataActive) }}">
                                            Master Data
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50 max-h-[75vh] overflow-y-auto">
                                            @can('customers.view')
                                                <a href="{{ route('customers.index') }}" class="{{ $dropdownLink(request()->routeIs('customers.index') || request()->routeIs('customers.edit')) }}">Customer</a>
                                                <a href="{{ route('customers.aktif') }}" class="{{ $dropdownLink(request()->routeIs('customers.aktif')) }}">Customer Aktif</a>
                                            @endcan

                                            @if (Auth::user()->hasPermission('kategori-produk-indoor.view') || Auth::user()->hasPermission('produk.view'))
                                                <p class="px-3 py-1.5 mx-1 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kategori Indoor</p>
                                                <hr class="mx-3 mb-1 border-gray-100">
                                                @can('kategori-produk-indoor.view')
                                                    <a href="{{ route('kategori-produk-indoor.index') }}" class="{{ $dropdownLink(request()->routeIs('kategori-produk-indoor.*')) }}">Data Divisi</a>
                                                @endcan
                                                @can('produk.view')
                                                    <a href="{{ route('detail-indoor.index') }}" class="{{ $dropdownLink(request()->routeIs('detail-indoor.*')) }}">Data Produk Indoor</a>
                                                @endcan
                                            @endif

                                            @if (Auth::user()->hasPermission('kategori-produk-indoor.view') || Auth::user()->hasPermission('harga-artwork.view'))
                                                <p class="px-3 py-1.5 mx-1 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kategori Artwork</p>
                                                <hr class="mx-3 mb-1 border-gray-100">
                                                @can('kategori-produk-indoor.view')
                                                    <a href="{{ route('kategori-produk-indoor.index') }}" class="{{ $dropdownLink(request()->routeIs('kategori-produk-indoor.*')) }}">Data Divisi</a>
                                                @endcan
                                                @can('harga-artwork.view')
                                                    <a href="{{ route('harga-artwork.index') }}" class="{{ $dropdownLink(request()->routeIs('harga-artwork.*')) }}">Data Bahan</a>
                                                @endcan
                                            @endif

                                            @if (Auth::user()->hasPermission('printer-outdoor.view') || Auth::user()->hasPermission('bahan-cetak-outdoor.view') || Auth::user()->hasPermission('harga-cetak-outdoor.view') || Auth::user()->hasPermission('harga-cetak-outdoor-khusus.view'))
                                                <p class="px-3 py-1.5 mx-1 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kategori Outdoor</p>
                                                <hr class="mx-3 mb-1 border-gray-100">
                                                @can('printer-outdoor.view')
                                                    <a href="{{ route('printer-outdoor.index') }}" class="{{ $dropdownLink(request()->routeIs('printer-outdoor.*')) }}">Data Printer</a>
                                                @endcan
                                                @can('bahan-cetak-outdoor.view')
                                                    <a href="{{ route('bahan-cetak-outdoor.index') }}" class="{{ $dropdownLink(request()->routeIs('bahan-cetak-outdoor.*')) }}">Bahan</a>
                                                @endcan
                                                @can('harga-cetak-outdoor.view')
                                                    <a href="{{ route('harga-cetak-outdoor.index') }}" class="{{ $dropdownLink(request()->routeIs('harga-cetak-outdoor.*')) }}">Standar Harga</a>
                                                @endcan
                                                @can('harga-cetak-outdoor-khusus.view')
                                                    <a href="{{ route('harga-cetak-outdoor-khusus.index') }}" class="{{ $dropdownLink(request()->routeIs('harga-cetak-outdoor-khusus.*')) }}">Harga Khusus VIP</a>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($showTransaksi)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($transaksiActive) }}">
                                            Transaksi
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            @can('order-indoor.view')
                                                <a href="{{ route('order-indoor.index') }}" class="{{ $dropdownLink(request()->routeIs('order-indoor.*')) }}">Order Indoor</a>
                                            @endcan
                                            @can('order-outdoor.view')
                                                <a href="{{ route('order-outdoor.index') }}" class="{{ $dropdownLink(request()->routeIs('order-outdoor.*')) }}">Order Outdoor</a>
                                            @endcan
                                            {{-- @can('order-artwork.view')
                                                <a href="{{ route('order-artwork.index') }}" class="{{ $dropdownLink(request()->routeIs('order-artwork.*')) }}">Order Artwork</a>
                                            @endcan --}}
                                        </div>
                                    </div>
                                @endif

                                @if ($showOperator)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($operatorActive) }}">
                                            Dashboard Operator
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            @can('file-monitor.view')
                                                <a href="{{ route('file.index') }}" class="{{ $dropdownLink(request()->routeIs('file.*')) }}">Penerima File</a>
                                            @endcan
                                            @can('kasir.view')
                                                <a href="{{ route('kasir.index') }}" class="{{ $dropdownLink(request()->routeIs('kasir.*')) }}">Kasir</a>
                                            @endcan
                                            @can('order-desain.view')
                                                <a href="{{ route('order-desain.index') }}" class="{{ $dropdownLink(request()->routeIs('order-desain.*')) }}">Layout / Desain</a>
                                            @endcan
                                            @can('order-cetak.view')
                                                <a href="{{ route('order-cetak.index') }}" class="{{ $dropdownLink(request()->routeIs('order-cetak.*')) }}">Cetak</a>
                                            @endcan
                                            @can('order-finishing.view')
                                                <a href="{{ route('order-finishing.index') }}" class="{{ $dropdownLink(request()->routeIs('order-finishing.*')) }}">Finishing</a>
                                            @endcan
                                            @can('order-qc.view')
                                                <a href="{{ route('order-qc.index') }}" class="{{ $dropdownLink(request()->routeIs('order-qc.*')) }}">Back Office</a>
                                            @endcan
                                            @can('order-bungkus.view')
                                                <a href="{{ route('order-bungkus.index') }}" class="{{ $dropdownLink(request()->routeIs('order-bungkus.*')) }}">Bungkus</a>
                                            @endcan
                                            @can('pengambilan.view')
                                                <a href="{{ route('pengambilan.index') }}" class="{{ $dropdownLink(request()->routeIs('pengambilan.*')) }}">Pengambilan Barang</a>
                                            @endcan
                                        </div>
                                    </div>
                                @endif

                                @if ($showAnalitik)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($analitikActive) }}">
                                            Analitik
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            @can('data-warehouse.view')
                                                <a href="{{ route('data-warehouse.index') }}" class="{{ $dropdownLink(request()->routeIs('data-warehouse.*')) }}">Data Warehouse</a>
                                            @endcan
                                            @can('monitoring-kinerja.view')
                                                <a href="{{ route('monitoring-kinerja.index') }}" class="{{ $dropdownLink(request()->routeIs('monitoring-kinerja.*')) }}">Monitoring Kinerja</a>
                                            @endcan
                                            @can('monitoring-transaksi.view')
                                                <a href="{{ route('monitoring-transaksi.index') }}" class="{{ $dropdownLink(request()->routeIs('monitoring-transaksi.*')) }}">Monitoring Transaksi</a>
                                            @endcan
                                            @can('papan-pantau.view')
                                                <a href="{{ route('papan-pantau.index') }}" class="{{ $dropdownLink(request()->routeIs('papan-pantau.*')) }}">Papan Pantau</a>
                                            @endcan
                                        </div>
                                    </div>
                                @endif

                                @if ($showPengaturan)
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                        <button type="button" @click="open = !open" class="{{ $navTopLink($pengaturanActive) }}">
                                            Pengaturan
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                        </button>
                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            @can('roles.manage')
                                                <a href="{{ route('roles.index') }}" class="{{ $dropdownLink(request()->routeIs('roles.*')) }}">Role & Akses</a>
                                                <a href="{{ route('users.index') }}" class="{{ $dropdownLink(request()->routeIs('users.*')) }}">User</a>
                                            @endcan
                                            @can('jasa-potong.manage')
                                                <a href="{{ route('jasa-potong.edit') }}" class="{{ $dropdownLink(request()->routeIs('jasa-potong.*')) }}">Jasa Potong Indoor</a>
                                            @endcan
                                            @can('jasa-potong-artwork.manage')
                                                <a href="{{ route('jasa-potong-artwork.edit') }}" class="{{ $dropdownLink(request()->routeIs('jasa-potong-artwork.*')) }}">Jasa Potong Artwork</a>
                                            @endcan
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="hidden lg:flex lg:items-center">
                            <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                <button @click="open = !open" class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ collect(explode(' ', Auth::user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <div class="text-left leading-tight">
                                        <div class="text-[13px] font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                        <div class="text-[11px] text-gray-400">{{ Auth::user()->role?->label ?? 'Belum ada role' }}</div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''">{!! $navIcon('chevron-down') !!}</svg>
                                </button>

                                <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
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
                        </div>

                        <div class="flex items-center lg:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-700">
                                <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                                <svg x-show="mobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu -->
                <div x-show="mobileMenuOpen" x-cloak class="lg:hidden border-t border-gray-200 max-h-[calc(100vh-4rem)] overflow-y-auto">
                    <div class="px-3 py-3 space-y-0.5 text-[13px]" @click="mobileMenuOpen = false">
                        @php $active = request()->routeIs('dashboard'); @endphp
                        <a href="{{ route('dashboard') }}" class="{{ $mobileLink($active) }}">Dashboard</a>
                        @can('preview-cetak.view')
                            <a href="{{ route('preview-cetak.index') }}" class="{{ $mobileLink(request()->routeIs('preview-cetak.*')) }}">Preview Cetak</a>
                        @endcan

                        @if ($showKeuangan)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Keuangan</p>
                            @can('keuangan.view')
                                <a href="{{ route('keuangan.kas-harian') }}" class="{{ $mobileLink(request()->routeIs('keuangan.kas-harian')) }}">Kas Harian</a>
                                <a href="{{ route('keuangan.rekap-kasir') }}" class="{{ $mobileLink(request()->routeIs('keuangan.rekap-kasir')) }}">Rekap per Kasir</a>
                                <a href="{{ route('keuangan.rekap-customer') }}" class="{{ $mobileLink(request()->routeIs('keuangan.rekap-customer')) }}">Total Order per Customer</a>
                                <a href="{{ route('keuangan.piutang') }}" class="{{ $mobileLink(request()->routeIs('keuangan.piutang')) }}">Piutang</a>
                            @endcan
                            @can('pengeluaran.view')
                                <a href="{{ route('pengeluaran.index') }}" class="{{ $mobileLink(request()->routeIs('pengeluaran.*')) }}">Pengeluaran</a>
                            @endcan
                            @can('payroll.view')
                                <a href="{{ route('payroll.index') }}" class="{{ $mobileLink(request()->routeIs('payroll.*')) }}">Payroll</a>
                            @endcan
                            @can('keuangan.view')
                                <a href="{{ route('keuangan.laba-rugi') }}" class="{{ $mobileLink(request()->routeIs('keuangan.laba-rugi')) }}">Laba Rugi</a>
                                <a href="{{ route('keuangan.jurnal-manual') }}" class="{{ $mobileLink(request()->routeIs('keuangan.jurnal-manual')) }}">Jurnal Manual</a>
                                <a href="{{ route('keuangan.laporan-ppn') }}" class="{{ $mobileLink(request()->routeIs('keuangan.laporan-ppn')) }}">Laporan PPN</a>
                                <a href="{{ route('keuangan.tutup-buku') }}" class="{{ $mobileLink(request()->routeIs('keuangan.tutup-buku*')) }}">Tutup Buku</a>
                            @endcan
                            @can('keuangan.pengaturan')
                                <a href="{{ route('keuangan.pengaturan.edit') }}" class="{{ $mobileLink(request()->routeIs('keuangan.pengaturan.edit')) }}">Pengaturan</a>
                            @endcan
                            @if ($canApproveCancel)
                                <a href="{{ route('pembatalan.index') }}" class="{{ $mobileLink(request()->routeIs('pembatalan.*')) }} flex items-center gap-1.5">
                                    Approval Batal
                                    @if ($pendingApprovalCount > 0)
                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[11px] font-bold leading-none">{{ $pendingApprovalCount }}</span>
                                    @endif
                                </a>
                            @endif
                            @if ($canApproveDiskon)
                                <a href="{{ route('diskon-approval.index') }}" class="{{ $mobileLink(request()->routeIs('diskon-approval.*')) }} flex items-center gap-1.5">
                                    Approval Diskon
                                    @if ($pendingDiskonCount > 0)
                                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[11px] font-bold leading-none">{{ $pendingDiskonCount }}</span>
                                    @endif
                                </a>
                            @endif
                        @endif

                        @if ($showMasterData)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Master Data</p>
                            @can('customers.view')
                                <a href="{{ route('customers.index') }}" class="{{ $mobileLink(request()->routeIs('customers.index') || request()->routeIs('customers.edit')) }}">Customer</a>
                                <a href="{{ route('customers.aktif') }}" class="{{ $mobileLink(request()->routeIs('customers.aktif')) }}">Customer Aktif</a>
                            @endcan
                            @can('kategori-produk-indoor.view')
                                <a href="{{ route('kategori-produk-indoor.index') }}" class="{{ $mobileLink(request()->routeIs('kategori-produk-indoor.*')) }}">Kategori Indoor — Data Divisi</a>
                            @endcan
                            @can('produk.view')
                                <a href="{{ route('detail-indoor.index') }}" class="{{ $mobileLink(request()->routeIs('detail-indoor.*')) }}">Kategori Indoor — Data Produk</a>
                            @endcan
                            @can('kategori-produk-indoor.view')
                                <a href="{{ route('kategori-produk-indoor.index') }}" class="{{ $mobileLink(request()->routeIs('kategori-produk-indoor.*')) }}">Kategori Artwork — Data Divisi</a>
                            @endcan
                            @can('harga-artwork.view')
                                <a href="{{ route('harga-artwork.index') }}" class="{{ $mobileLink(request()->routeIs('harga-artwork.*')) }}">Kategori Artwork — Data Bahan</a>
                            @endcan
                            @can('printer-outdoor.view')
                                <a href="{{ route('printer-outdoor.index') }}" class="{{ $mobileLink(request()->routeIs('printer-outdoor.*')) }}">Kategori Outdoor — Data Printer</a>
                            @endcan
                            @can('bahan-cetak-outdoor.view')
                                <a href="{{ route('bahan-cetak-outdoor.index') }}" class="{{ $mobileLink(request()->routeIs('bahan-cetak-outdoor.*')) }}">Kategori Outdoor — Bahan</a>
                            @endcan
                            @can('harga-cetak-outdoor.view')
                                <a href="{{ route('harga-cetak-outdoor.index') }}" class="{{ $mobileLink(request()->routeIs('harga-cetak-outdoor.*')) }}">Kategori Outdoor — Standar Harga</a>
                            @endcan
                            @can('harga-cetak-outdoor-khusus.view')
                                <a href="{{ route('harga-cetak-outdoor-khusus.index') }}" class="{{ $mobileLink(request()->routeIs('harga-cetak-outdoor-khusus.*')) }}">Kategori Outdoor — Harga Khusus VIP</a>
                            @endcan
                        @endif

                        @if ($showTransaksi)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Transaksi</p>
                            @can('order-indoor.view')
                                <a href="{{ route('order-indoor.index') }}" class="{{ $mobileLink(request()->routeIs('order-indoor.*')) }}">Order Indoor</a>
                            @endcan
                            @can('order-outdoor.view')
                                <a href="{{ route('order-outdoor.index') }}" class="{{ $mobileLink(request()->routeIs('order-outdoor.*')) }}">Order Outdoor</a>
                            @endcan
                            @can('order-artwork.view')
                                <a href="{{ route('order-artwork.index') }}" class="{{ $mobileLink(request()->routeIs('order-artwork.*')) }}">Order Artwork</a>
                            @endcan
                        @endif

                        @if ($showOperator)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Dashboard Operator</p>
                            @can('file-monitor.view')
                                <a href="{{ route('file.index') }}" class="{{ $mobileLink(request()->routeIs('file.*')) }}">Penerima File</a>
                            @endcan
                            @can('kasir.view')
                                <a href="{{ route('kasir.index') }}" class="{{ $mobileLink(request()->routeIs('kasir.*')) }}">Kasir</a>
                            @endcan
                            @can('order-desain.view')
                                <a href="{{ route('order-desain.index') }}" class="{{ $mobileLink(request()->routeIs('order-desain.*')) }}">Layout/Edit</a>
                            @endcan
                            @can('order-cetak.view')
                                <a href="{{ route('order-cetak.index') }}" class="{{ $mobileLink(request()->routeIs('order-cetak.*')) }}">Cetak</a>
                            @endcan
                            @can('order-finishing.view')
                                <a href="{{ route('order-finishing.index') }}" class="{{ $mobileLink(request()->routeIs('order-finishing.*')) }}">Finishing</a>
                            @endcan
                            @can('order-qc.view')
                                <a href="{{ route('order-qc.index') }}" class="{{ $mobileLink(request()->routeIs('order-qc.*')) }}">Back Office</a>
                            @endcan
                            @can('order-bungkus.view')
                                <a href="{{ route('order-bungkus.index') }}" class="{{ $mobileLink(request()->routeIs('order-bungkus.*')) }}">Bungkus</a>
                            @endcan
                            @can('pengambilan.view')
                                <a href="{{ route('pengambilan.index') }}" class="{{ $mobileLink(request()->routeIs('pengambilan.*')) }}">Pengambilan Barang</a>
                            @endcan
                        @endif

                        @if ($showAnalitik)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Analitik</p>
                            @can('data-warehouse.view')
                                <a href="{{ route('data-warehouse.index') }}" class="{{ $mobileLink(request()->routeIs('data-warehouse.*')) }}">Data Warehouse</a>
                            @endcan
                            @can('monitoring-kinerja.view')
                                <a href="{{ route('monitoring-kinerja.index') }}" class="{{ $mobileLink(request()->routeIs('monitoring-kinerja.*')) }}">Monitoring Kinerja</a>
                            @endcan
                            @can('monitoring-transaksi.view')
                                <a href="{{ route('monitoring-transaksi.index') }}" class="{{ $mobileLink(request()->routeIs('monitoring-transaksi.*')) }}">Monitoring Transaksi</a>
                            @endcan
                            @can('papan-pantau.view')
                                <a href="{{ route('papan-pantau.index') }}" class="{{ $mobileLink(request()->routeIs('papan-pantau.*')) }}">Papan Pantau</a>
                            @endcan
                        @endif

                        @if ($showPengaturan)
                            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Pengaturan</p>
                            @can('roles.manage')
                                <a href="{{ route('roles.index') }}" class="{{ $mobileLink(request()->routeIs('roles.*')) }}">Role & Akses</a>
                                <a href="{{ route('users.index') }}" class="{{ $mobileLink(request()->routeIs('users.*')) }}">User</a>
                            @endcan
                            @can('jasa-potong.manage')
                                <a href="{{ route('jasa-potong.edit') }}" class="{{ $mobileLink(request()->routeIs('jasa-potong.*')) }}">Jasa Potong Indoor</a>
                            @endcan
                            @can('jasa-potong-artwork.manage')
                                <a href="{{ route('jasa-potong-artwork.edit') }}" class="{{ $mobileLink(request()->routeIs('jasa-potong-artwork.*')) }}">Jasa Potong Artwork</a>
                            @endcan
                        @endif
                    </div>

                    <div class="pt-3 pb-3 border-t border-gray-200">
                        <div class="flex items-center gap-3 px-4">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                {{ collect(explode(' ', Auth::user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-400">{{ Auth::user()->role?->label ?? 'Belum ada role' }}</div>
                            </div>
                        </div>
                        <div class="mt-3 px-2 space-y-0.5 text-[13px]">
                            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            @isset($header)
                <header class="bg-white border-b border-gray-200">
                    <div class="px-4 sm:px-6 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

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

        @include('partials.chat-widget')
    </body>
</html>
