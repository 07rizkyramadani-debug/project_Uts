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
    <body class="app">
        @include('layouts.navigation')

        @isset($header)
            <header class="page-head">
                <div class="page-head-inner">{{ $header }}</div>
            </header>
        @endisset

        <main class="app-main">
            {{ $slot }}
        </main>
    </body>
</html>
