@props(['url' => null, 'title' => '', 'seed' => 0, 'width' => '2.6rem'])
@php
    $palettes = [['#4f46e5', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5']];
    [$g1, $g2] = $palettes[((int) $seed) % count($palettes)];
@endphp
<span {{ $attributes->merge(['class' => 'mini-cover']) }} style="--w: {{ $width }}; background: linear-gradient(150deg, {{ $g1 }}, {{ $g2 }});" title="{{ $title }}">
    <i>{{ mb_strtoupper(mb_substr($title, 0, 1)) }}</i>
    @if ($url)<img src="{{ $url }}" alt="" loading="lazy" onerror="this.remove()">@endif
</span>
