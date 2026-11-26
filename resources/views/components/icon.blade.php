@props(['name' => 'book'])
@php
$paths = [
    'book' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5v14zM20 17v4H6.5A2.5 2.5 0 0 1 4 18.5',
    'home' => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10',
    'users' => 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    'user' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
    'swap' => 'M7 7h13l-3-3M17 17H4l3 3',
    'check' => 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3',
    'alert' => 'M12 9v4M12 17h.01M10.3 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z',
    'xcircle' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM15 9l-6 6M9 9l6 6',
    'shield' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 12l2 2 4-4',
    'menu' => 'M4 6h16M4 12h16M4 18h16',
    'x' => 'M6 6l12 12M18 6L6 18',
    'clock' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 6v6l4 2',
    'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
    'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
    'search' => 'M21 21l-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16z',
    'arrow' => 'M5 12h14M13 5l7 7-7 7',
    'lock' => 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4',
    'bolt' => 'M13 2L3 14h8l-1 8 10-12h-8l1-8z',
    'coin' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 6v12M9 9.5c0-1 1.3-1.5 3-1.5s3 .7 3 2-1.3 1.8-3 2-3 .7-3 2 1.3 2 3 2 3-.5 3-1.5',
    'code' => 'M16 18l6-6-6-6M8 6l-6 6 6 6',
    'layers' => 'M12 2l10 5-10 5L2 7l10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
];
@endphp
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}><path d="{{ $paths[$name] ?? $paths['book'] }}"/></svg>
