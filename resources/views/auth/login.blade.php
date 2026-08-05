<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'SpektrumX') }}</title>

    <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .login-input { padding-left: 40px; }
        .login-input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: color-mix(in srgb, var(--color-text) 55%, transparent); pointer-events: none; display: flex; }
        .login-toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: color-mix(in srgb, var(--color-text) 55%, transparent); display: flex; }
        .login-toggle-pw:hover { color: var(--color-accent); }
    </style>
</head>
<body style="font-family: var(--font-body); margin: 0;">
    {{-- background-size: contain (bukan cover) supaya gambar promosi selalu
         tampil utuh, tidak terpotong atas/bawah walau rasio layar sempit.
         bg-black juga dipasang di <body> supaya celah pembulatan/scrollbar
         di tepi tetap hitam, tidak nembus putih. --}}
    <div class="min-h-screen w-full flex items-center justify-end p-4 sm:p-8 lg:p-16 bg-black"
         style="background-image: url('{{ asset('images/spektrumLogin6.png') }}'); background-size: contain; background-position: center; background-repeat: no-repeat;">

        <form method="POST" action="{{ route('login') }}" class="blueprint"
              style="position: relative; width: min(420px, 100%); background: var(--color-bg); border: 1px solid var(--color-divider);
                     padding: var(--space-8); display: flex; flex-direction: column; gap: var(--space-4); box-shadow: var(--shadow-lg);">
            <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
            @csrf

            <div style="text-align: center; margin-bottom: 4px;">
                <div class="card-kicker" style="justify-content: center;">Spektrum</div>
                <h1 style="margin: 4px 0 0;">Login</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 2px;">Masuk Dashboard</p>
            </div>

            <x-auth-session-status class="text-sm text-center" :status="session('status')" />

            <div class="field">
                <label for="email">Email</label>
                <div style="position: relative;">
                    <span class="login-input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"></path></svg>
                    </span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           placeholder="nama@spektrumx.test" class="input login-input">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="field" x-data="{ show: false }">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <span class="login-input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    </span>
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                           placeholder="••••••••" class="input login-input" style="padding-right: 40px;">
                    <button type="button" @click="show = !show" aria-label="Toggle password" class="login-toggle-pw">
                        <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.4 0-10-7-10-7a18.6 18.6 0 0 1 4.22-5.19M9.9 4.24A9.12 9.12 0 0 1 12 4c6.4 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <label class="radio">
                    <input name="remember" type="checkbox">
                    <span class="dot"></span>
                    Ingat saya
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size: 14px;">Lupa password?</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary blueprint btn-block" style="height: 44px; margin-top: 4px;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Login
            </button>
        </form>
    </div>
</body>
</html>
