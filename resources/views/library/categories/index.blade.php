@php
    $isAdmin = auth()->user()->isAdmin();
    $palettes = [['#6366f1', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5']];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Data Master</h2>
                <p class="page-sub">Kelola penulis, kategori, dan penerbit yang dipakai pada katalog buku.</p>
            </div>
            <div class="bk-hero-side">
                @if ($isAdmin)<a href="{{ route('categories.create') }}" class="btn btn-primary">+ Tambah Kategori</a>@endif
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')
        @include('library._master_tabs')

        <div class="pg-summary">
            <div class="pg-sum"><small>Total kategori</small><b>{{ $summary['all'] }}</b></div>
            <div class="pg-sum tone-b"><small>Kategori utama</small><b>{{ $summary['roots'] }}</b></div>
            <div class="pg-sum tone-g"><small>Sub-kategori</small><b>{{ $summary['subs'] }}</b></div>
            <div class="pg-sum tone-a"><small>Buku berkategori</small><b>{{ $summary['books'] }}</b></div>
        </div>

        @if ($categories->isEmpty())
            <div class="bk-empty"><div class="bk-empty-ico">🏷</div><b>Belum ada kategori</b><span>Tambahkan kategori pertama untuk mengelompokkan buku.</span></div>
        @else
            <div class="cg-grid">
                @foreach ($categories as $category)
                    @php [$g1, $g2] = $palettes[$category->id % count($palettes)]; @endphp
                    <article class="cg-card" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">
                        <header class="cg-head">
                            <span class="cg-ico">{{ strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                            <div class="cg-title">
                                <h3>{{ $category->name }}</h3>
                                <span>{{ $category->books_count }} buku · {{ $category->children->count() }} sub-kategori</span>
                            </div>
                            @if ($isAdmin)
                                <div class="cg-tools">
                                    <a href="{{ route('categories.edit', $category) }}" title="Edit kategori" aria-label="Edit {{ $category->name }}">✎</a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ e($category->name) }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus kategori" aria-label="Hapus {{ $category->name }}">🗑</button>
                                    </form>
                                </div>
                            @endif
                        </header>

                        @if ($category->description)<p class="ms-text">{{ $category->description }}</p>@endif

                        <ul class="cg-subs">
                            @forelse ($category->children as $child)
                                <li class="cg-sub">
                                    <span class="cg-dot"></span>
                                    <span class="cg-name">{{ $child->name }}</span>
                                    <span class="cg-count">{{ $child->books_count }}</span>
                                    @if ($isAdmin)
                                        <span class="cg-mini">
                                            <a href="{{ route('categories.edit', $child) }}" title="Edit {{ $child->name }}" aria-label="Edit {{ $child->name }}">✎</a>
                                            <form action="{{ route('categories.destroy', $child) }}" method="POST" onsubmit="return confirm('Hapus sub-kategori {{ e($child->name) }}?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus {{ $child->name }}" aria-label="Hapus {{ $child->name }}">🗑</button>
                                            </form>
                                        </span>
                                    @endif
                                </li>
                            @empty
                                <li class="cg-empty">Belum ada sub-kategori</li>
                            @endforelse
                        </ul>

                        @if ($isAdmin)
                            <a href="{{ route('categories.create', ['parent_id' => $category->id]) }}" class="cg-add">＋ Tambah sub-kategori</a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
