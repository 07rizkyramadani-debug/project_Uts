@php
    $isPustakawan = auth()->user()->isPustakawan();
    $isAdmin = auth()->user()->isAdmin();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Koleksi Buku</h2>
                <p class="page-sub">
                    @if ($isAdmin) Kelola koleksi perpustakaan: tambah, ubah, dan hapus buku beserta data penulis, kategori, dan penerbit.
                    @elseif ($isPustakawan) Pantau stok buku dan laporkan ke admin jika stoknya habis.
                    @else Temukan buku favoritmu, lalu ajukan peminjaman dalam satu klik. @endif
                </p>
            </div>
            <div class="bk-hero-side">
                <span class="bk-hero-count">{{ $books->total() }} buku</span>
                @if ($isAdmin)
                    <a href="{{ route('books.trashed') }}" class="btn btn-ghost">Buku terhapus</a>
                    <a href="{{ route('books.create') }}" class="btn btn-primary">+ Tambah Buku</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')

        @if (auth()->user()->isUser() && !$member)
            <div class="bk-note">Keanggotaan Anda tidak aktif, jadi belum bisa mengajukan peminjaman.
                Hubungi pustakawan untuk mengaktifkannya kembali.</div>
        @endif

        <form method="GET" class="bk-filter">
            <div class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>
                <input type="text" name="q" value="{{ request('q') }}"
                    placeholder="Cari judul, ISBN, atau penulis...">
            </div>
            <select name="category" aria-label="Kategori">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <label class="bk-check">
                <input type="checkbox" name="available" value="1" @checked(request()->boolean('available'))>
                Hanya yang tersedia
            </label>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>

        @if ($books->isEmpty())
            <div class="bk-empty">
                <div class="bk-empty-ico">📚</div>
                <b>Buku tidak ditemukan</b>
                <span>Coba kata kunci atau kategori lain.</span>
            </div>
        @else
            <div class="bk-grid">
                @foreach ($books as $book)
                    @php
                        $stockTone = $book->stock === 0 ? 'out' : ($book->stock <= 2 ? 'low' : 'ok');
                        $pct = $book->total_stock > 0 ? round(($book->stock / $book->total_stock) * 100) : 0;
                    @endphp
                    <article class="bk-card">
                        <a href="{{ route('books.show', $book) }}" class="bk-media"
                            aria-label="Lihat detail {{ $book->title }}">
                            <x-book-cover :book="$book" />
                            <span class="bk-badge bk-badge-{{ $stockTone }}">
                                {{ $book->stock === 0 ? 'Habis' : ($stockTone === 'low' ? 'Sisa ' . $book->stock : 'Tersedia') }}
                            </span>
                        </a>
                        <div class="bk-body">
                            <div class="bk-cat">{{ $book->categories->first()?->name ?? 'Umum' }}</div>
                            <h3 class="bk-title"><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a>
                            </h3>
                            <div class="bk-author">{{ $book->authors->pluck('name')->join(', ') ?: '-' }}</div>
                            @if ($isAdmin)
                                <div class="bk-admin-info">
                                    <span title="Penerbit">🏢 {{ $book->publisher?->name ?? 'Tanpa penerbit' }}</span>
                                    <span title="ISBN">ISBN {{ $book->isbn->format() }}</span>
                                    @if (in_array($book->id, $reportedBookIds, true))
                                        <a href="{{ route('stock-reports.index') }}" class="bk-reported">⚠ Stok dilaporkan pustakawan</a>
                                    @endif
                                </div>
                            @endif

                            <div class="bk-stock">
                                <div class="bk-stock-row"><span>Stok</span><b>{{ $book->stock }} /
                                        {{ $book->total_stock }}</b></div>
                                <div class="bk-bar"><i class="bk-bar-{{ $stockTone }}"
                                        style="width: {{ $pct }}%"></i></div>
                            </div>

                            @if ($isAdmin)
                                <div class="bk-admin-actions">
                                    <a href="{{ route('books.edit', $book) }}" class="bk-act bk-act-edit">✎ Edit</a>
                                    <form action="{{ route('books.destroy', $book) }}" method="POST"
                                        onsubmit="return confirm('Hapus buku &quot;{{ e($book->title) }}&quot; (soft delete)?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bk-act bk-act-del">🗑 Hapus</button>
                                    </form>
                                </div>
                            @elseif ($isPustakawan)
                                @if ($book->stock > 0)
                                    <button type="button" class="bk-btn bk-btn-off" disabled>Stok tersedia</button>
                                @elseif (in_array($book->id, $reportedBookIds, true))
                                    <button type="button" class="bk-btn bk-btn-off" disabled>Sudah dilaporkan</button>
                                @else
                                    <form action="{{ route('books.report-stock', $book) }}" method="POST"
                                        onsubmit="return confirm('Laporkan stok buku &quot;{{ e($book->title) }}&quot; yang habis ke admin?');">
                                        @csrf
                                        <button type="submit" class="bk-btn bk-btn-warn">⚠ Laporkan ke Admin</button>
                                    </form>
                                @endif
                            @elseif ($member && $book->stock > 0)
                                <form action="{{ route('my-loans.store') }}" method="POST"
                                    onsubmit="return confirm('Ajukan peminjaman buku &quot;{{ e($book->title) }}&quot;?');">
                                    @csrf
                                    <input type="hidden" name="items[0][book_id]" value="{{ $book->id }}">
                                    <input type="hidden" name="items[0][quantity]" value="1">
                                    <button type="submit" class="bk-btn bk-btn-primary">📚 Ajukan Pinjam</button>
                                </form>
                            @else
                                <button type="button" class="bk-btn bk-btn-off"
                                    disabled>{{ $book->stock === 0 ? 'Stok habis' : 'Belum bisa pinjam' }}</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $books->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
