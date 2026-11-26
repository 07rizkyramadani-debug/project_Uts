{{-- Tab data master katalog (butuh $masterCounts dari view composer) --}}
<div class="pg-tabs">
    <a href="{{ route('authors.index') }}" class="pg-tab {{ request()->routeIs('authors.*') ? 'on' : '' }}">👤 Penulis <b>{{ $masterCounts['authors'] }}</b></a>
    <a href="{{ route('categories.index') }}" class="pg-tab {{ request()->routeIs('categories.*') ? 'on' : '' }}">🏷 Kategori <b>{{ $masterCounts['categories'] }}</b></a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('publishers.index') }}" class="pg-tab {{ request()->routeIs('publishers.*') ? 'on' : '' }}">🏢 Penerbit <b>{{ $masterCounts['publishers'] }}</b></a>
    @endif
</div>
