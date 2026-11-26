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
                @if ($isAdmin)<a href="{{ route('authors.create') }}" class="btn btn-primary">+ Tambah Penulis</a>@endif
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')
        @include('library._master_tabs')

        <div class="pg-summary">
            <div class="pg-sum"><small>Total penulis</small><b>{{ $summary['all'] }}</b></div>
            <div class="pg-sum tone-g"><small>Punya buku</small><b>{{ $summary['with_books'] }}</b></div>
            <div class="pg-sum tone-a"><small>Belum punya buku</small><b>{{ $summary['without_books'] }}</b></div>
        </div>

        <form method="GET" class="bk-filter">
            <div class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama penulis...">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>

        @if ($authors->isEmpty())
            <div class="bk-empty"><div class="bk-empty-ico">👤</div><b>Penulis tidak ditemukan</b><span>Coba kata kunci lain{{ $isAdmin ? ' atau tambahkan penulis baru.' : '.' }}</span></div>
        @else
            <div class="ms-grid">
                @foreach ($authors as $author)
                    @php [$g1, $g2] = $palettes[$author->id % count($palettes)]; $titles = $author->books->pluck('title'); @endphp
                    <article class="ms-card">
                        <div class="ms-top">
                            <span class="mb-avatar" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">{{ strtoupper(mb_substr($author->name, 0, 1)) }}</span>
                            <div class="ms-id"><h3 title="{{ $author->name }}">{{ $author->name }}</h3><span>Penulis</span></div>
                            <span class="lib-badge {{ $author->books_count > 0 ? 'lib-badge-blue' : 'lib-badge-gray' }}">{{ $author->books_count }} buku</span>
                        </div>
                        @if ($author->bio)<p class="ms-text">{{ $author->bio }}</p>@endif
                        <div class="ms-chips">
                            @forelse ($titles->take(3) as $title)
                                <span class="ms-chip" title="{{ $title }}">📖 {{ \Illuminate\Support\Str::limit($title, 34) }}</span>
                            @empty
                                <span class="ms-chip ms-chip-off">Belum ada buku</span>
                            @endforelse
                            @if ($titles->count() > 3)<span class="ms-chip ms-chip-more">+{{ $titles->count() - 3 }} lainnya</span>@endif
                        </div>
                        @if ($isAdmin)
                            <div class="ms-actions">
                                <a href="{{ route('authors.edit', $author) }}" class="bk-act bk-act-edit">✎ Edit</a>
                                <form action="{{ route('authors.destroy', $author) }}" method="POST" onsubmit="return confirm('Hapus penulis {{ e($author->name) }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bk-act bk-act-del">🗑 Hapus</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $authors->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
