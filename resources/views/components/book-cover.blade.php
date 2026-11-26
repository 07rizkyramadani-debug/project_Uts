@props(['book', 'class' => ''])
@php
    // Sampul otomatis (gradien + judul) selalu disiapkan sebagai cadangan: tampil bila buku belum punya
    // gambar atau gambarnya gagal dimuat. Warna tetap per buku.
    $palettes = [
        ['#4f46e5', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'],
        ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5'],
    ];
    [$from, $to] = $palettes[$book->id % count($palettes)];
@endphp
<div {{ $attributes->merge(['class' => 'bk-cover '.$class]) }}>
    <div class="bk-cover-auto" style="background: linear-gradient(150deg, {{ $from }}, {{ $to }});">
        <span class="bk-cover-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </span>
        <span class="bk-cover-title">{{ $book->title }}</span>
        <span class="bk-cover-author">{{ $book->authors->first()?->name }}</span>
    </div>
    @if ($book->cover_url)
        <img src="{{ $book->cover_url }}" alt="Sampul {{ $book->title }}" loading="lazy" onerror="this.remove()">
    @endif
</div>
