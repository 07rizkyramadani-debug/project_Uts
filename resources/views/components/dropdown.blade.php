@props(['align' => 'right', 'width' => '48', 'contentClasses' => '', 'dropdownClasses' => ''])
<div class="dd" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">{{ $trigger }}</div>
    <div x-show="open" x-transition.opacity.duration.120ms class="dd-menu {{ $align === 'left' ? 'dd-left' : 'dd-right' }}" style="display: none;" @click="open = false">
        {{ $content }}
    </div>
</div>
