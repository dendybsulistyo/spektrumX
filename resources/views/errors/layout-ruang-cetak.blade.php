{{--
    Kerangka halaman error ramah (tema Ruang Cetak). Dipakai 403/404/419/500.
    Untuk kembali ke halaman error bawaan Laravel: hapus folder resources/views/errors.
--}}
@php
    $user = auth()->user();
    $home = $user ? route('dashboard') : (Route::has('login') ? route('login') : url('/'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name', 'SpektrumX') }}</title>
    <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">
    <style>
        :root { --ink: #1b2236; --paper: #f5f2ea; --sheet: #fffdf8; --rule: #e3ddcf; --rule-strong: #cfc7b5; --muted: #77736a; --cyan: #0f8fb3; --magenta: #c8246c; --yellow: #f2c200; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #262a33; background: var(--paper); }
        .card { position: relative; width: 100%; max-width: 560px; padding: 40px 36px 30px; text-align: center; background: var(--sheet); border: 1px solid var(--rule); border-radius: 14px; box-shadow: 0 1px 0 var(--rule), 0 30px 60px -36px rgba(27, 34, 54, .5); }
        .card::before { content: ""; position: absolute; left: 36px; right: 36px; top: -1px; height: 4px; border-radius: 0 0 2px 2px; background: linear-gradient(90deg, var(--cyan) 0 25%, var(--magenta) 25% 50%, var(--yellow) 50% 75%, var(--ink) 75% 100%); }
        .icon { display: inline-flex; width: 76px; height: 76px; align-items: center; justify-content: center; margin-bottom: 18px; font-size: 38px; background: #f1ece0; border-radius: 50%; }
        .code { margin-bottom: 6px; font-family: 'IBM Plex Mono', monospace; font-size: 12px; letter-spacing: .18em; color: var(--muted); }
        h1 { margin: 0 0 10px; font-size: 24px; font-weight: 700; color: var(--ink); }
        p { margin: 0 auto; max-width: 440px; font-size: 14.5px; line-height: 1.65; color: #5d5a52; }
        .detail { display: inline-block; margin-top: 14px; padding: 7px 12px; font-size: 13px; color: var(--ink); background: #f1ece0; border-radius: 8px; }
        .hint { margin-top: 18px; padding-top: 16px; font-size: 13px; color: var(--muted); border-top: 1px dashed var(--rule-strong); }
        .hint b { color: var(--ink); }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 22px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; height: 42px; padding: 0 18px; font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 8px; cursor: pointer; }
        .btn-primary { color: #fff; background: var(--ink); border: 1px solid var(--ink); }
        .btn-primary:hover { background: #2c3550; }
        .btn-ghost { color: var(--ink); background: #fff; border: 1px solid var(--rule-strong); }
        .btn-ghost:hover { background: #f4efe3; }
        .meta { margin-top: 18px; font-family: 'IBM Plex Mono', monospace; font-size: 11px; color: #a8a294; word-break: break-all; }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">{{ $icon }}</div>
        <div class="code">KODE {{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        @if (! empty($detail))
            <div class="detail">{{ $detail }}</div>
        @endif

        @if (! empty($hint))
            <div class="hint">{!! $hint !!}</div>
        @endif

        <div class="actions">
            @if ($code === '419')
                <button type="button" class="btn btn-primary" onclick="location.reload()">Muat ulang halaman</button>
            @else
                <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : location.href = '{{ $home }}'">&larr; Kembali</button>
                <a href="{{ $home }}" class="btn btn-primary">{{ $user ? 'Ke Dashboard' : 'Ke halaman login' }}</a>
            @endif
        </div>

        <div class="meta">
            {{ request()->path() === '/' ? '/' : '/'.request()->path() }}@if ($user) &middot; {{ $user->name }}@if ($user->role?->label) ({{ $user->role->label }})@endif @endif
        </div>
    </main>
</body>
</html>
