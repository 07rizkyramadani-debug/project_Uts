<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2>Pinjaman Saya</h2>
                <p class="page-sub">Pantau buku yang sedang dipinjam, status pengajuan, dan riwayat peminjaman Anda.</p>
            </div>
            @if ($member)
                <a href="{{ route('books.index') }}" class="btn btn-primary">📚 Cari Buku</a>
            @endif
        </div>
    </x-slot>

    <div class="container stack">
        @include('library._flash')

        @if (! $member)
            <div class="card ml-empty">
                <x-icon name="user" />
                <h3>Keanggotaan tidak aktif</h3>
                <p>Data anggota Anda tidak aktif atau telah dihapus. Hubungi pustakawan untuk mengaktifkannya kembali.</p>
            </div>
        @else
            <section class="stats">
                <div class="stat tone-indigo"><div class="stat-ico"><x-icon name="book" /></div><div class="stat-label">Total Buku Dipinjam</div><div class="stat-value">{{ number_format($stats['total_books']) }}</div></div>
                <div class="stat tone-sky"><div class="stat-ico"><x-icon name="swap" /></div><div class="stat-label">Sedang Dipinjam</div><div class="stat-value">{{ number_format($stats['active_books']) }}</div></div>
                <div class="stat tone-amber"><div class="stat-ico"><x-icon name="clock" /></div><div class="stat-label">Menunggu Persetujuan</div><div class="stat-value">{{ number_format($stats['pending']) }}</div></div>
                <div class="stat tone-rose"><div class="stat-ico"><x-icon name="coin" /></div><div class="stat-label">Total Denda</div><div class="stat-value">Rp {{ number_format($stats['fine'], 0, ',', '.') }}</div></div>
            </section>

            @if ($stats['overdue'] > 0)
                <div class="alert alert-error"><x-icon name="alert" /><div><strong>{{ $stats['overdue'] }} peminjaman terlambat.</strong> Segera kembalikan buku ke perpustakaan agar denda tidak bertambah.</div></div>
            @endif

            <div class="lib-tabs">
                @php($st = request('status'))
                <a href="{{ route('my-loans.index') }}" class="lib-tab {{ ! $st ? 'lib-tab-active' : '' }}">Semua</a>
                <a href="{{ route('my-loans.index', ['status' => 'pending']) }}" class="lib-tab {{ $st === 'pending' ? 'lib-tab-active' : '' }}">Pengajuan</a>
                <a href="{{ route('my-loans.index', ['status' => 'borrowed']) }}" class="lib-tab {{ $st === 'borrowed' ? 'lib-tab-active' : '' }}">Dipinjam</a>
                <a href="{{ route('my-loans.index', ['status' => 'overdue']) }}" class="lib-tab {{ $st === 'overdue' ? 'lib-tab-active' : '' }}">Terlambat</a>
                <a href="{{ route('my-loans.index', ['status' => 'returned']) }}" class="lib-tab {{ $st === 'returned' ? 'lib-tab-active' : '' }}">Dikembalikan</a>
                <a href="{{ route('my-loans.index', ['status' => 'rejected']) }}" class="lib-tab {{ $st === 'rejected' ? 'lib-tab-active' : '' }}">Ditolak</a>
            </div>

            @if ($filtered !== null)
                <section class="card">
                    <h3 class="card-title">Hasil filter</h3>
                    <p class="card-sub">{{ $filtered->count() }} peminjaman ditemukan.</p>
                    @include('library.loans._mine_table', ['loans' => $filtered, 'covers' => $covers])
                </section>
            @else
                {{-- ===== Sedang berjalan ===== --}}
                <section>
                    <div class="sec-head"><h3>Sedang berjalan</h3><span>{{ $active->count() }} peminjaman</span></div>
                    @if ($active->isEmpty())
                        <div class="card ml-empty">
                            <x-icon name="book" />
                            <h3>Belum ada peminjaman berjalan</h3>
                            <p>Telusuri katalog dan ajukan buku yang ingin Anda baca.</p>
                            <a href="{{ route('books.index') }}" class="btn btn-primary">Telusuri Katalog</a>
                        </div>
                    @else
                        <div class="ml-grid">
                            @foreach ($active as $loan)
                                @php($tone = $loan->isPending() ? 'amber' : ($loan->isOverdue() ? 'red' : 'blue'))
                                <article class="ml-card ml-{{ $tone }}">
                                    <div class="ml-top">
                                        <div class="ml-covers">
                                            @foreach ($loan->details->take(3) as $d)
                                                <x-mini-cover :url="$covers[$d->book_id]['cover_url'] ?? null" :title="$d->book_title" :seed="$d->book_id" width="3.1rem" />
                                            @endforeach
                                        </div>
                                        <div class="ml-info">
                                            <div class="ml-no">{{ $loan->loan_number }}</div>
                                            <div class="ml-sub">diajukan {{ $loan->created_at->format('d M Y') }}</div>
                                        </div>
                                        <div class="ml-state"><x-loan-status :loan="$loan" /></div>
                                    </div>
                                    <ul class="ml-books">
                                        @foreach ($loan->details as $detail)
                                            <li>{{ $detail->book_title }}@if ($detail->quantity > 1) ×{{ $detail->quantity }}@endif <span>{{ $detail->book_isbn }}</span></li>
                                        @endforeach
                                    </ul>
                                    @if ($loan->notes)<div class="ml-note">Catatan: {{ $loan->notes }}</div>@endif
                                    <div class="ml-meta">
                                        <div><small>Tgl. pinjam</small><b>{{ $loan->borrowed_at?->format('d M Y') ?? '-' }}</b></div>
                                        <div>
                                            <small>Jatuh tempo</small><b>{{ $loan->due_at?->format('d M Y') ?? '-' }}</b>
                                            @if ($loan->isBorrowed())
                                                <em class="{{ $loan->isOverdue() ? 'late' : '' }}">
                                                    @if ($loan->isOverdue()) terlambat {{ (int) ceil($loan->due_at->diffInDays(now(), true)) }} hari
                                                    @else {{ (int) ceil(now()->diffInDays($loan->due_at, true)) }} hari lagi @endif
                                                </em>
                                            @endif
                                        </div>
                                        <div>
                                            <small>Denda</small><b>Rp {{ number_format($loan->accruedFine(), 0, ',', '.') }}</b>
                                            @if ($loan->isBorrowed() && $loan->accruedFine() > 0)<em class="late">berjalan</em>@endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- ===== Riwayat peminjaman ===== --}}
                <section id="riwayat">
                    <div class="sec-head"><h3>Riwayat peminjaman</h3><span>{{ $stats['history'] }} transaksi selesai</span></div>
                    <div class="card">
                        @include('library.loans._mine_table', ['loans' => $history, 'covers' => $covers])
                        <div class="mt-4">{{ $history->fragment('riwayat')->links('library.pagination') }}</div>
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-app-layout>
