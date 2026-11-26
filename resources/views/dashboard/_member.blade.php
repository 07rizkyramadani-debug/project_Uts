@if (! $member)
    <div class="card ml-empty">
        <x-icon name="user" />
        <h3>Keanggotaan Anda tidak aktif</h3>
        <p>Anda tetap bisa menelusuri katalog. Untuk mengajukan peminjaman, hubungi pustakawan agar keanggotaan Anda diaktifkan kembali.</p>
    </div>
@else
    <section class="stats">
        <div class="stat tone-indigo"><div class="stat-ico"><x-icon name="book" /></div><div class="stat-label">Total Buku Dipinjam</div><div class="stat-value">{{ number_format($myStats['total_books']) }}</div><div class="stat-note">sepanjang waktu</div></div>
        <div class="stat tone-sky"><div class="stat-ico"><x-icon name="swap" /></div><div class="stat-label">Sedang Dipinjam</div><div class="stat-value">{{ number_format($myStats['active_books']) }}</div><div class="stat-note">dari maks. {{ $member['max_loans'] }} buku</div></div>
        <div class="stat tone-amber"><div class="stat-ico"><x-icon name="clock" /></div><div class="stat-label">Menunggu Persetujuan</div><div class="stat-value">{{ number_format($myStats['pending']) }}</div><div class="stat-note">pengajuan</div></div>
        <div class="stat tone-rose"><div class="stat-ico"><x-icon name="coin" /></div><div class="stat-label">Total Denda</div><div class="stat-value">Rp {{ number_format($myStats['fine'], 0, ',', '.') }}</div><div class="stat-note">{{ $myStats['overdue'] > 0 ? $myStats['overdue'].' terlambat' : 'tidak ada keterlambatan' }}</div></div>
    </section>

    @if ($myStats['overdue'] > 0)
        <div class="alert alert-error"><x-icon name="alert" /><div><strong>{{ $myStats['overdue'] }} peminjaman terlambat.</strong> Segera kembalikan buku agar denda tidak bertambah.</div></div>
    @endif

    <div class="dh-cols">
        <section class="card">
            <div class="sec-head"><h3>Sedang berjalan</h3><a href="{{ route('my-loans.index') }}">Lihat semua →</a></div>
            @forelse ($activeLoans as $loan)
                @foreach ($loan->details as $d)
                    @php($cv = $covers[$d->book_id] ?? null)
                    <div class="dh-item">
                        <x-mini-cover :url="$cv?->cover_url" :title="$d->book_title" :seed="$d->book_id" width="3rem" />
                        <div class="dh-item-main">
                            <b>{{ $d->book_title }}@if ($d->quantity > 1) ×{{ $d->quantity }}@endif</b>
                            <span>{{ $loan->loan_number }}@if ($loan->due_at) · jatuh tempo {{ $loan->due_at->format('d M Y') }}@endif</span>
                        </div>
                        <div class="dh-item-side">
                            <x-loan-status :loan="$loan" />
                            @if ($loan->isBorrowed())
                                <small class="{{ $loan->isOverdue() ? 'late' : '' }}">
                                    @if ($loan->isOverdue()) terlambat {{ (int) ceil($loan->due_at->diffInDays(now(), true)) }} hari
                                    @else {{ (int) ceil(now()->diffInDays($loan->due_at, true)) }} hari lagi @endif
                                </small>
                            @endif
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="dh-empty">Belum ada peminjaman berjalan. <a href="{{ route('books.index') }}">Cari buku sekarang</a></div>
            @endforelse
        </section>

        <section class="card">
            <div class="sec-head"><h3>Kuota pinjam</h3></div>
            @php($used = min($member['active_loans'], $member['max_loans']))
            <div class="dh-quota">
                <div class="dh-ring" style="--p: {{ $member['max_loans'] > 0 ? round($used / $member['max_loans'] * 100) : 0 }}"><span><b>{{ $used }}</b>/{{ $member['max_loans'] }}</span></div>
                <div>
                    <p><b>{{ $member['max_loans'] - $used }}</b> buku lagi bisa Anda pinjam.</p>
                    <small>Masa pinjam {{ $member['loan_days'] }} hari. Denda Rp1.000 per hari keterlambatan.</small>
                </div>
            </div>
            <div class="sec-head" style="margin-top:1.4rem"><h3>Riwayat terbaru</h3><a href="{{ route('my-loans.index') }}#riwayat">Semua riwayat →</a></div>
            @forelse ($historyLoans as $loan)
                @php($d = $loan->details->first())
                <div class="dh-item dh-item-sm">
                    <x-mini-cover :url="($covers[$d->book_id] ?? null)?->cover_url" :title="$d->book_title" :seed="$d->book_id" width="2.2rem" />
                    <div class="dh-item-main">
                        <b>{{ $d->book_title }}@if ($loan->details->count() > 1) +{{ $loan->details->count() - 1 }} lainnya @endif</b>
                        <span>{{ $loan->isReturned() ? 'dikembalikan '.$loan->returned_at->format('d M Y') : 'ditolak' }}</span>
                    </div>
                    <x-loan-status :loan="$loan" />
                </div>
            @empty
                <div class="dh-empty">Riwayat peminjaman akan muncul di sini.</div>
            @endforelse
        </section>
    </div>
@endif

<section>
    <div class="sec-head"><h3>Rekomendasi buku tersedia</h3><a href="{{ route('books.index') }}">Lihat katalog →</a></div>
    <div class="dh-books">
        @foreach ($recommended as $book)
            <a href="{{ route('books.show', $book) }}" class="dh-book">
                <div class="dh-book-cover"><x-book-cover :book="$book" /></div>
                <b>{{ $book->title }}</b>
                <span>{{ $book->authors->first()?->name }}</span>
            </a>
        @endforeach
    </div>
</section>
