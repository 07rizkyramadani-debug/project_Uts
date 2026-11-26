@php
    $palettes = [['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5'], ['#6366f1', '#7c3aed']];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Data Master</h2>
                <p class="page-sub">Kelola penulis, kategori, dan penerbit yang dipakai pada katalog buku.</p>
            </div>
            <div class="bk-hero-side"><a href="{{ route('publishers.create') }}" class="btn btn-primary">+ Tambah Penerbit</a></div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')
        @include('library._master_tabs')

        <div class="pg-summary">
            <div class="pg-sum"><small>Total penerbit</small><b>{{ $summary['all'] }}</b></div>
            <div class="pg-sum tone-g"><small>Punya buku</small><b>{{ $summary['with_books'] }}</b></div>
            <div class="pg-sum tone-b"><small>Kota asal</small><b>{{ $summary['cities'] }}</b></div>
        </div>

        <form method="GET" class="bk-filter">
            <div class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kota penerbit...">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>

        @if ($publishers->isEmpty())
            <div class="bk-empty"><div class="bk-empty-ico">🏢</div><b>Penerbit tidak ditemukan</b><span>Coba kata kunci lain atau tambahkan penerbit baru.</span></div>
        @else
            <div class="ms-grid">
                @foreach ($publishers as $publisher)
                    @php [$g1, $g2] = $palettes[$publisher->id % count($palettes)]; @endphp
                    <article class="ms-card">
                        <div class="ms-top">
                            <span class="mb-avatar ms-ico" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">🏢</span>
                            <div class="ms-id"><h3 title="{{ $publisher->name }}">{{ $publisher->name }}</h3><span>{{ $publisher->city ? '📍 '.$publisher->city : 'Kota belum diisi' }}</span></div>
                            <span class="lib-badge {{ $publisher->books_count > 0 ? 'lib-badge-blue' : 'lib-badge-gray' }}">{{ $publisher->books_count }} buku</span>
                        </div>
                        <div class="ms-actions">
                            <a href="{{ route('publishers.edit', $publisher) }}" class="bk-act bk-act-edit">✎ Edit</a>
                            <form action="{{ route('publishers.destroy', $publisher) }}" method="POST" onsubmit="return confirm('Hapus penerbit {{ e($publisher->name) }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="bk-act bk-act-del">🗑 Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $publishers->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
