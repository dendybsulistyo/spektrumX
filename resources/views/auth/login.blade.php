<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'SpektrumX') }}</title>

    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#0b0d14]">
    <div class="min-h-screen w-full flex">

        {{-- Panel kiri — identitas merek, gelap & tegas --}}
        <div class="hidden lg:flex lg:w-[46%] xl:w-[42%] relative flex-col justify-between overflow-hidden bg-[#0b0d14] text-white px-14 py-12">
            {{-- Aksen garis halus & glow indigo, bukan gambar/font online --}}
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-40 -left-32 h-96 w-96 rounded-full bg-indigo-600/25 blur-3xl"></div>
                <div class="absolute bottom-[-8rem] right-[-6rem] h-[28rem] w-[28rem] rounded-full bg-indigo-500/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.04]"
                     style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 42px 42px;"></div>
            </div>

            <div class="relative flex items-center gap-3">
                <div class="w-9 h-9 rounded-md bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">S</div>
                <span class="text-[15px] font-semibold tracking-[0.28em] uppercase text-white/90">Spektrum</span>
            </div>

            <div class="relative max-w-md">
                <span class="inline-block text-[11px] font-semibold tracking-[0.3em] uppercase text-indigo-400 mb-5">
                    Sistem Manajemen Order
                </span>
                <h1 class="text-4xl xl:text-[2.65rem] font-semibold leading-[1.15] tracking-tight text-white">
                    Kendalikan seluruh<br>alur produksi<br><span class="text-indigo-400">dari satu tempat.</span>
                </h1>
                <p class="mt-6 text-sm leading-relaxed text-white/50 max-w-sm">
                    Desain, cetak, finishing, QC, hingga pengambilan barang —
                    terpantau dan tercatat.
                </p>
            </div>

            <div class="relative flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-white/30">
                <span class="h-px w-8 bg-white/20"></span>
                <span>&copy; {{ date('Y') }} Spektrum</span>
            </div>
        </div>

        {{-- Panel kanan — form login --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12 sm:px-10 bg-[#f5f5f7]">
            <div class="w-full max-w-[380px]">

                <div class="mb-9 lg:hidden flex items-center gap-3">
                    <div class="w-8 h-8 rounded-md bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">S</div>
                    <span class="text-sm font-semibold tracking-[0.28em] uppercase text-gray-900">Spektrum</span>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-semibold tracking-tight text-gray-900">Masuk ke akun Anda</h2>
                    <p class="mt-1.5 text-sm text-gray-500">Silakan masukkan kredensial untuk melanjutkan.</p>
                </div>

                <x-auth-session-status class="mb-5 text-sm" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold tracking-wide uppercase text-gray-500 mb-1.5">
                            Email
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"></path></svg>
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                   placeholder="nama@spektrumx.test"
                                   class="block w-full rounded-md border-gray-300 bg-white pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-1 h-11">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold tracking-wide uppercase text-gray-500">
                                Password
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    Lupa password?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                            </span>
                            <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="block w-full rounded-md border-gray-300 bg-white pl-10 pr-10 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-1 h-11">
                            <button type="button" @click="show = !show" aria-label="Toggle password"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600">
                                <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.4 0-10-7-10-7a18.6 18.6 0 0 1 4.22-5.19M9.9 4.24A9.12 9.12 0 0 1 12 4c6.4 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <label class="flex items-center gap-2.5 select-none">
                        <input name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0">
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>

                    <button type="submit"
                            class="group relative flex w-full items-center justify-center gap-2 rounded-md bg-gray-900 h-11 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Masuk
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-0.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>

            </div>
        </div>
    </div>
</body>
</html>
