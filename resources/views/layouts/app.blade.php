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
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="flex min-h-screen">

            <!-- Sidebar -->
            <aside class="fixed left-0 top-0 h-screen w-64 bg-gray-900 text-gray-100 flex flex-col py-6 z-40">
                <div class="px-6 mb-8">
                    <h1 class="text-lg font-bold text-white">{{ config('app.name', 'SpektrumX') }}</h1>
                    <p class="text-xs text-gray-400 mt-1">Admin Percetakan</p>
                </div>

                <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                           {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                        Dashboard
                    </a>

                    @if (Auth::user()->hasPermission('customers.view') || Auth::user()->hasPermission('produk.view') || Auth::user()->hasPermission('operators.view') || Auth::user()->hasPermission('printers.view'))
                        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-wider text-gray-500">Master Data</p>
                    @endif

                    @can('customers.view')
                        <a href="{{ route('customers.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('customers.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Customer
                        </a>
                    @endcan

                    @can('produk.view')
                        <a href="{{ route('produk.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('produk.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Produk
                        </a>
                    @endcan

                    @can('operators.view')
                        <a href="{{ route('operators.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('operators.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Operator
                        </a>
                    @endcan

                    @can('printers.view')
                        <a href="{{ route('printers.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('printers.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Printer
                        </a>
                    @endcan

                    @can('order-indoor.view')
                        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-wider text-gray-500">Transaksi</p>

                        <a href="{{ route('order-indoor.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('order-indoor.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Order Indoor
                        </a>
                    @endcan

                    @can('roles.manage')
                        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-wider text-gray-500">Pengaturan</p>

                        <a href="{{ route('roles.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('roles.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            Role & Akses
                        </a>

                        <a href="{{ route('users.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition
                               {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800' }}">
                            User
                        </a>
                    @endcan
                </nav>

                <div class="px-3 mt-4 border-t border-gray-800 pt-4">
                    <div class="px-3 mb-2">
                        <div class="text-sm text-gray-300">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500">{{ Auth::user()->role?->label ?? 'Belum ada role' }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}"
                       class="block px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-800">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-800">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main content -->
            <div class="flex-1 ml-64">
                @isset($header)
                    <header class="bg-white shadow-sm">
                        <div class="px-6 py-5">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="p-6">
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
    </body>
</html>
