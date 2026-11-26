@props(['active'])
<a {{ $attributes->merge(['class' => ($active ?? false) ? 'active' : '']) }}>{{ $slot }}</a>
