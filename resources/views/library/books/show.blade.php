<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('books.index') }}" class="lib-btn lib-btn-ghost" style="margin-bottom:.8rem">← Kembali ke daftar buku</a>
    </x-slot>

    <div class="container">
        @include('library._flash')
        @php($stockTone = $book->stock === 0 ? 'out' : ($book->stock <= 2 ? 'low' : 'ok'))

        <div class="card bk-detail">
            <div class="bk-detail-cover">
                <x-book-cover :book="$book" />
            </div>

            <div>
                <div class="bk-chips" style="margin-bottom:.6rem">
                    @forelse ($book->categories as $category)
                        <span class="bk-chip">{{ $category->parent ? $category->parent->name.' › ' : '' }}{{ $category->name }}</span>
                    @empty
                        <span class="bk-chip">Umum</span>
                    @endforelse
                </div>
                <h1 style="margin:0;font-size:1.7rem;font-weight:800;letter-spacing:-.02em;line-height:1.2">{{ $book->title }}</h1>
                <p style="margin:.35rem 0 0;color:var(--muted)">oleh <b style="color:var(--ink-2)">{{ $book->authors->pluck('name')->join(', ') ?: '-' }}</b></p>

                <div class="bk-meta">
                    <div><span>ISBN</span><b>{{ $book->isbn->format() }}</b></div>
                    <div><span>Penerbit</span><b>{{ $book->publisher?->name ?? '-' }}</b></div>
                    <div><span>Tahun terbit</span><b>{{ $book->published_year ?? '-' }}</b></div>
                    <div><span>Stok</span><b><span class="bk-badge-inline bk-badge-{{ $stockTone }}">{{ $book->stock }} dari {{ $book->total_stock }}</span></b></div>
                </div>

                @if ($book->description)
                    <p style="color:var(--ink-2);line-height:1.8;margin:0 0 1.25rem">{{ $book->description }}</p>
                @endif

                @if (auth()->user()->isPustakawan())
                    @if ($book->stock > 0)
                        <p class="bk-note">Stok masih tersedia ({{ $book->stock }} dari {{ $book->total_stock }}). Peminjaman hanya dapat diajukan oleh user.</p>
                    @elseif ($hasOpenReport)
                        <p class="bk-note">Stok habis. Sudah dilaporkan ke admin dan menunggu tindak lanjut.</p>
                    @else
                        <form method="POST" action="{{ route('books.report-stock', $book) }}" class="lib-form" style="border-top:1px solid var(--line);padding-top:1.1rem">
                            @csrf
                            <div class="lib-section-title">Stok habis</div>
                            <div>
                                <label for="note">Catatan untuk admin</label>
                                <textarea id="note" name="note" rows="2" maxlength="255" placeholder="Catatan (opsional)"></textarea>
                            </div>
                            <div class="lib-form-actions">
                                <button type="submit" class="lib-btn lib-btn-danger">⚠ Laporkan ke Admin</button>
                            </div>
                        </form>
                    @endif
                @elseif ($member)
                    @if ($book->stock > 0)
                        <form method="POST" action="{{ route('my-loans.store') }}" class="lib-form" style="border-top:1px solid var(--line);padding-top:1.1rem">
                            @csrf
                            <input type="hidden" name="items[0][book_id]" value="{{ $book->id }}">
                            <div class="lib-section-title">Ajukan peminjaman</div>
                            <div class="lib-item-row">
                                <input type="number" name="items[0][quantity]" value="1" min="1" max="{{ min(5, $book->stock) }}" aria-label="Jumlah buku" title="Jumlah">
                            </div>
                            <div>
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="2" placeholder="Catatan untuk pustakawan (opsional)"></textarea>
                            </div>
                            <div class="lib-hint">Maksimal {{ $member['max_loans'] }} buku selama {{ $member['loan_days'] }} hari. Jatuh tempo dihitung sejak pustakawan menyetujui dan menyerahkan buku.</div>
                            <div class="lib-form-actions">
                                <button type="submit" class="lib-btn lib-btn-primary">📚 Ajukan Pinjam</button>
                            </div>
                        </form>
                    @else
                        <p class="bk-note">Stok buku ini sedang habis.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
