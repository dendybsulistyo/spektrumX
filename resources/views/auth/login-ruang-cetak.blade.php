{{--
    Login tema "Ruang Cetak" (navy tinta + kertas + aksen CMYK).
    Untuk kembali ke login lama: di AuthenticatedSessionController@create
    ganti view('auth.login-ruang-cetak') menjadi view('auth.login').
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'SpektrumX') }}</title>
    <x-app-favicon />

    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --ink: #1b2236; --ink-soft: #2c3550; --paper: #f5f2ea; --sheet: #fffdf8;
            --rule: #e3ddcf; --rule-strong: #cfc7b5; --muted: #77736a; --text: #262a33;
            --cyan: #0f8fb3; --magenta: #c8246c; --yellow: #f2c200;
            --sans: 'IBM Plex Sans', system-ui, sans-serif; --mono: 'IBM Plex Mono', ui-monospace, monospace;
        }
        * { box-sizing: border-box; }
        body.rc-login { margin: 0; min-height: 100vh; display: flex; font-family: var(--sans); color: var(--text); background: var(--paper); -webkit-font-smoothing: antialiased; }
        .cmyk { background: linear-gradient(90deg, var(--cyan) 0 25%, var(--magenta) 25% 50%, var(--yellow) 50% 75%, #fff 75% 100%); }

        /* ---------- Panel kiri: tinta ---------- */
        .rc-brand { position: relative; display: flex; flex-direction: column; justify-content: space-between; width: 44%; max-width: 640px; padding: 48px 56px; overflow: hidden; color: #fff; background: var(--ink); }
        .rc-brand::before { content: ""; position: absolute; inset: 0; opacity: .05; background-image: radial-gradient(#fff 1px, transparent 1.2px); background-size: 14px 14px; }
        .rc-brand > * { position: relative; }
        .rc-logo { display: flex; align-items: center; gap: 12px; }
        .rc-mark { position: relative; display: flex; width: 40px; height: 40px; align-items: center; justify-content: center; overflow: hidden; font-weight: 700; font-size: 17px; color: var(--ink); background: #fff; border-radius: 8px; }
        .rc-mark i { position: absolute; left: 0; right: 0; bottom: 0; height: 5px; }
        .rc-logo span { font-size: 14px; font-weight: 600; letter-spacing: .3em; text-transform: uppercase; }
        .rc-eyebrow { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 22px; font-family: var(--mono); font-size: 11px; letter-spacing: .18em; text-transform: uppercase; color: #aab2c8; }
        .rc-eyebrow i { width: 34px; height: 4px; border-radius: 1px; }
        .rc-headline { margin: 0; font-size: clamp(30px, 3.1vw, 44px); font-weight: 600; line-height: 1.14; letter-spacing: -.01em; }
        .rc-headline em { font-style: normal; color: #7fd0ea; }
        .rc-lede { max-width: 380px; margin: 22px 0 0; font-size: 14px; line-height: 1.65; color: #b9bfd1; }
        .rc-steps { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 30px; }
        .rc-steps span { padding: 4px 10px; font-family: var(--mono); font-size: 11px; color: #d7dbe7; border: 1px solid rgba(255, 255, 255, .18); border-radius: 999px; }
        .rc-steps b { color: rgba(255, 255, 255, .35); font-weight: 400; }
        .rc-foot { display: flex; align-items: center; justify-content: space-between; gap: 16px; font-family: var(--mono); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: rgba(255, 255, 255, .38); }
        .rc-register { width: 46px; height: 46px; color: rgba(255, 255, 255, .28); }

        /* ---------- Panel kanan: kertas ---------- */
        .rc-form-side { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
        .rc-card { position: relative; width: 100%; max-width: 400px; padding: 34px 32px 30px; background: var(--sheet); border: 1px solid var(--rule); border-radius: 12px; box-shadow: 0 1px 0 var(--rule), 0 24px 48px -30px rgba(27, 34, 54, .45); }
        .rc-card::before { content: ""; position: absolute; left: 32px; right: 32px; top: -1px; height: 4px; border-radius: 0 0 2px 2px; background: linear-gradient(90deg, var(--cyan) 0 25%, var(--magenta) 25% 50%, var(--yellow) 50% 75%, var(--ink) 75% 100%); }
        .rc-mobile-logo { display: none; }
        .rc-card h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); }
        .rc-card .rc-sub { margin: 6px 0 26px; font-size: 13.5px; color: var(--muted); }
        .rc-field { margin-bottom: 18px; }
        .rc-label-row { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 6px; }
        .rc-label { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .rc-link { font-size: 12.5px; font-weight: 600; color: #0b6a86; text-decoration: none; }
        .rc-link:hover { text-decoration: underline; }
        .rc-input-wrap { position: relative; }
        .rc-input-wrap > svg { position: absolute; left: 12px; top: 50%; width: 18px; height: 18px; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
        .rc-input { width: 100%; height: 46px; padding: 0 42px; font: inherit; font-size: 14.5px; color: var(--text); background: #fff; border: 1px solid var(--rule-strong); border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
        .rc-input::placeholder { color: #a8a294; }
        .rc-input:focus { border-color: var(--cyan); box-shadow: 0 0 0 3px color-mix(in srgb, var(--cyan) 20%, transparent); }
        .rc-eye { position: absolute; right: 6px; top: 50%; display: flex; width: 34px; height: 34px; align-items: center; justify-content: center; color: var(--muted); background: none; border: 0; border-radius: 6px; transform: translateY(-50%); cursor: pointer; }
        .rc-eye:hover { color: var(--ink); background: #f4efe3; }
        .rc-eye svg { width: 18px; height: 18px; }
        .rc-error { margin-top: 6px; font-size: 12.5px; color: var(--magenta); }
        .rc-error ul { margin: 0; padding: 0; list-style: none; }
        .rc-remember { display: flex; align-items: center; gap: 9px; margin: 4px 0 22px; font-size: 13.5px; color: #5d5a52; cursor: pointer; user-select: none; }
        .rc-remember input { width: 16px; height: 16px; accent-color: var(--ink); }
        .rc-submit { display: flex; width: 100%; height: 46px; align-items: center; justify-content: center; gap: 8px; font: inherit; font-size: 14.5px; font-weight: 600; color: #fff; background: var(--ink); border: 0; border-radius: 8px; cursor: pointer; transition: background .15s ease; }
        .rc-submit:hover { background: var(--ink-soft); }
        .rc-submit:focus-visible { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--cyan) 35%, transparent); }
        .rc-submit svg { width: 16px; height: 16px; transition: transform .15s ease; }
        .rc-submit:hover svg { transform: translateX(2px); }
        .rc-status { margin-bottom: 18px; padding: 10px 12px; font-size: 13px; color: #2e7a4f; background: #e6f2ea; border-radius: 8px; }
        .rc-note { margin-top: 22px; padding-top: 16px; font-size: 12px; color: var(--muted); text-align: center; border-top: 1px dashed var(--rule-strong); }

        @media (max-width: 960px) {
            body.rc-login { flex-direction: column; }
            .rc-brand { display: none; }
            .rc-form-side { align-items: flex-start; padding-top: 0; }
            .rc-band { display: block !important; }
            .rc-mobile-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
            .rc-mobile-logo .rc-mark { color: #fff; background: var(--ink); width: 34px; height: 34px; font-size: 15px; }
            .rc-mobile-logo span { font-size: 13px; font-weight: 600; letter-spacing: .28em; text-transform: uppercase; color: var(--ink); }
            .rc-card { margin-top: 28px; padding: 28px 22px 24px; }
        }
        .rc-band { display: none; height: 8px; background: linear-gradient(90deg, var(--cyan) 0 25%, var(--magenta) 25% 50%, var(--yellow) 50% 75%, var(--ink) 75% 100%) !important; }
    </style>
</head>
<body class="rc-login">
    <div class="rc-band cmyk"></div>

    {{-- Panel kiri: identitas --}}
    <aside class="rc-brand">
        <div class="rc-logo">
            <div class="rc-mark">S<i class="cmyk"></i></div>
            <span>Spektrum</span>
        </div>

        <div>
            <div class="rc-eyebrow"><i class="cmyk"></i>Sistem Manajemen Order</div>
            <h1 class="rc-headline">Kendalikan seluruh<br>alur produksi<br><em>dari satu tempat.</em></h1>
            <p class="rc-lede">Dari kasir hingga pengambilan barang — setiap order terpantau, setiap tahap tercatat.</p>
            <div class="rc-steps">
                <span>Layout</span><b>›</b><span>Cetak</span><b>›</b><span>Finishing</span><b>›</b><span>Back Office</span><b>›</b><span>Bungkus</span><b>›</b><span>Ambil</span>
            </div>
        </div>

        <div class="rc-foot">
            <span>&copy; {{ date('Y') }} Spektrum</span>
            {{-- Tanda register cetak --}}
            <svg class="rc-register" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4">
                <circle cx="24" cy="24" r="11"/><circle cx="24" cy="24" r="5"/><path d="M24 4v40M4 24h40"/>
            </svg>
        </div>
    </aside>

    {{-- Panel kanan: form --}}
    <main class="rc-form-side">
        <div class="rc-card">
            <div class="rc-mobile-logo">
                <div class="rc-mark">S</div>
                <span>Spektrum</span>
            </div>

            <h2>Masuk ke akun Anda</h2>
            <p class="rc-sub">Silakan masukkan email dan password untuk melanjutkan.</p>

            @if (session('status'))
                <div class="rc-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="rc-field">
                    <div class="rc-label-row"><label for="email" class="rc-label">Email</label></div>
                    <div class="rc-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"></path></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               placeholder="nama@spektrumx.test" class="rc-input">
                    </div>
                    @error('email')<div class="rc-error">{{ $message }}</div>@enderror
                </div>

                <div class="rc-field" x-data="{ show: false }">
                    <div class="rc-label-row">
                        <label for="password" class="rc-label">Password</label>
                    </div>
                    <div class="rc-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password"
                               placeholder="••••••••" class="rc-input">
                        <button type="button" class="rc-eye" @click="show = !show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                            <svg x-show="!show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg x-show="show" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.4 0-10-7-10-7a18.6 18.6 0 0 1 4.22-5.19M9.9 4.24A9.12 9.12 0 0 1 12 4c6.4 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        </button>
                    </div>
                    @error('password')<div class="rc-error">{{ $message }}</div>@enderror
                </div>

                <label class="rc-remember">
                    <input name="remember" type="checkbox">
                    Ingat saya
                </label>

                <button type="submit" class="rc-submit">
                    Masuk
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>

            <div class="rc-note">Kendala masuk? Hubungi admin Spektrum.</div>
        </div>
    </main>
</body>
</html>
