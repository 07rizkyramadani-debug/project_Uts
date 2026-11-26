@props(['href' => '/'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'brand']) }}>
    <span class="brand-mark"><x-application-logo /></span>
    <span class="brand-text">Perpustakaan Digital<small>Sistem Manajemen</small></span>
</a>
