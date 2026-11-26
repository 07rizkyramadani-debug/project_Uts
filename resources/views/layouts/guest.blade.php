<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Perpustakaan Digital</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v=6">
        <link rel="stylesheet" href="{{ asset('css/motion.css') }}?v=2">
        <script src="{{ asset('js/motion.js') }}?v=2" defer></script>
    </head>
    <body class="guest">
        <div class="auth-shell">
            <aside class="auth-aside">
                <x-brand href="{{ url('/') }}" />

                <div>
                    <h1>Kelola perpustakaan<br>dengan lebih mudah.</h1>
                    <p class="lead">Katalog buku, data anggota, hingga peminjaman dan denda dalam satu sistem yang rapi dan aman.</p>
                    <ul class="auth-features">
                        <li><span class="ico"><x-icon name="book" /></span><div><b>Katalog terpusat</b><span>Buku, penulis, kategori, dan stok selalu sinkron.</span></div></li>
                        <li><span class="ico"><x-icon name="swap" /></span><div><b>Peminjaman tervalidasi</b><span>Stok, batas pinjam, dan denda dihitung otomatis.</span></div></li>
                        <li><span class="ico"><x-icon name="shield" /></span><div><b>Akses sesuai peran</b><span>Admin, pustakawan, dan anggota punya hak berbeda.</span></div></li>
                    </ul>
                </div>

                <div class="auth-foot">UTS Backend Development &middot; Modular Monolith Laravel 13</div>
            </aside>

            <main class="auth-main">
                <div class="auth-panel">
                    <x-brand href="{{ url('/') }}" />
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
