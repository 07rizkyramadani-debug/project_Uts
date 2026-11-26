<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') - Perpustakaan Digital</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v=6">
    </head>
    <body class="guest" style="background: var(--bg);">
        <div style="min-height:100vh;display:grid;place-items:center;padding:2rem;">
            <div class="card" style="max-width:30rem;text-align:center;padding:2.5rem 2rem;">
                <x-brand href="{{ url('/') }}" style="margin-bottom:1.5rem" />
                <div style="font-size:4rem;font-weight:800;letter-spacing:-.04em;line-height:1;background:linear-gradient(135deg,#6366f1,#7c3aed);-webkit-background-clip:text;background-clip:text;color:transparent;">@yield('code')</div>
                <h2 style="margin:.6rem 0 .4rem;font-size:1.35rem;font-weight:800;">@yield('heading')</h2>
                <p class="muted" style="margin:0 0 1.5rem;">@yield('message')</p>
                <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        </div>
    </body>
</html>
