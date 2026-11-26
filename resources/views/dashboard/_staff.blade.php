@php $u = auth()->user(); @endphp
<section class="stats stats-6">
    <div class="stat tone-indigo"><div class="stat-ico"><x-icon name="book" /></div><div class="stat-label">Judul Buku</div><div class="stat-value">{{ number_format($stats['books']) }}</div></div>
    <div class="stat tone-green"><div class="stat-ico"><x-icon name="check" /></div><div class="stat-label">Buku Tersedia</div><div class="stat-value">{{ number_format($stats['available']) }}</div></div>
    <div class="stat tone-sky"><div class="stat-ico"><x-icon name="users" /></div><div class="stat-label">Anggota</div><div class="stat-value">{{ number_format($stats['members']) }}</div></div>
    <div class="stat tone-amber"><div class="stat-ico"><x-icon name="swap" /></div><div class="stat-label">Sedang Dipinjam</div><div class="stat-value">{{ number_format($stats['active']) }}</div></div>
    <div class="stat tone-sky"><div class="stat-ico"><x-icon name="clock" /></div><div class="stat-label">Menunggu Persetujuan</div><div class="stat-value"><a class="lib-link" href="{{ route('loans.index', ['status' => 'pending']) }}">{{ number_format($stats['pending']) }}</a></div></div>
    <div class="stat tone-rose"><div class="stat-ico"><x-icon name="alert" /></div><div class="stat-label">Terlambat</div><div class="stat-value">{{ number_format($stats['overdue']) }}</div></div>
</section>

<div class="dh-cols">
    <section class="card">
        <div class="sec-head"><h3>Aktivitas 7 hari terakhir</h3><span>pengajuan &amp; peminjaman baru</span></div>
        @php $max = max(1, $week->max('count')); @endphp
        <div class="dh-bars">
            @foreach ($week as $day)
                <div class="dh-bar">
                    <em>{{ $day['count'] }}</em>
                    <div class="dh-bar-fill" style="height: {{ max(4, round($day['count'] / $max * 100)) }}%"></div>
                    <span>{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card">
        <div class="sec-head"><h3>Komposisi peminjaman</h3><span>semua status</span></div>
        @php
            $total = collect($donut)->sum('count');
            $acc = 0; $stops = [];
            foreach ($donut as $seg) {
                if ($seg['count'] > 0) {
                    $from = $acc / max($total, 1) * 100; $acc += $seg['count']; $to = $acc / max($total, 1) * 100;
                    $stops[] = "{$seg['color']} {$from}% {$to}%";
                }
            }
            $gradient = $total > 0 ? 'conic-gradient('.implode(', ', $stops).')' : 'conic-gradient(#e5e7eb 0 100%)';
        @endphp
        <div class="dh-donut-wrap">
            <div class="dh-donut" style="background: {{ $gradient }}"><span><b>{{ number_format($total) }}</b>transaksi</span></div>
            <ul class="dh-legend">
                @foreach ($donut as $seg)
                    <li><i style="background: {{ $seg['color'] }}"></i>{{ $seg['label'] }}<b>{{ $seg['count'] }}</b></li>
                @endforeach
            </ul>
        </div>
    </section>
</div>

<div class="dh-cols">
    <section class="card">
        <div class="sec-head"><h3>Perlu tindakan</h3><a href="{{ route('loans.index', ['status' => 'pending']) }}">Semua pengajuan →</a></div>
        @forelse ($pendingList as $loan)
            <div class="dh-item">
                <span class="dh-dot dh-dot-amber"><x-icon name="clock" /></span>
                <div class="dh-item-main">
                    <b>{{ $loan->member_name }}</b>
                    <span>{{ $loan->details->first()?->book_title }}@if ($loan->details->count() > 1) +{{ $loan->details->count() - 1 }} buku @endif · {{ $loan->created_at->locale('id')->diffForHumans() }}</span>
                </div>
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-ghost btn-sm">Tinjau</a>
            </div>
        @empty
            <div class="dh-empty">🎉 Tidak ada pengajuan yang menunggu.</div>
        @endforelse
    </section>

    <section class="card">
        <div class="sec-head"><h3>Peminjaman terlambat</h3><a href="{{ route('loans.index', ['status' => 'overdue']) }}">Lihat semua →</a></div>
        @forelse ($overdueList as $loan)
            <div class="dh-item">
                <span class="dh-dot dh-dot-red"><x-icon name="alert" /></span>
                <div class="dh-item-main">
                    <b>{{ $loan->member_name }}</b>
                    <span>{{ $loan->loan_number }} · terlambat {{ (int) ceil($loan->due_at->diffInDays(now(), true)) }} hari</span>
                </div>
                <b class="dh-fine">Rp {{ number_format($loan->accruedFine(), 0, ',', '.') }}</b>
            </div>
        @empty
            <div class="dh-empty">✅ Tidak ada peminjaman yang terlambat.</div>
        @endforelse
    </section>
</div>

<div class="dh-cols dh-cols-wide">
    <section class="card">
        <div class="sec-head"><h3>Peminjaman terbaru</h3><a href="{{ route('loans.index') }}">Lihat semua →</a></div>
        <div style="overflow-x:auto">
            <table class="lib-table">
                <thead><tr><th>No. Pinjam</th><th>Anggota</th><th>Jatuh Tempo</th><th class="text-center">Status</th></tr></thead>
                <tbody>
                    @forelse ($recentLoans as $loan)
                        <tr>
                            <td class="lib-nowrap"><a class="lib-link" href="{{ route('loans.show', $loan) }}">{{ $loan->loan_number }}</a></td>
                            <td>{{ $loan->member_name }}</td>
                            <td class="lib-nowrap">{{ $loan->due_at?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="text-center"><span class="lib-badge {{ match (true) { $loan->isPending() => 'lib-badge-amber', $loan->isRejected() => 'lib-badge-gray', $loan->isReturned() => 'lib-badge-green', $loan->isOverdue() => 'lib-badge-red', default => 'lib-badge-blue' } }}">{{ match (true) { $loan->isPending() => 'Menunggu', $loan->isRejected() => 'Ditolak', $loan->isReturned() => 'Dikembalikan', $loan->isOverdue() => 'Terlambat', default => 'Dipinjam' } }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">Belum ada peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="sec-head"><h3>Buku terpopuler</h3><span>paling sering dipinjam</span></div>
        @forelse ($popular as $i => $row)
            <div class="dh-item dh-item-sm">
                <span class="dh-rank">{{ $i + 1 }}</span>
                <x-mini-cover :url="$row['book']->cover_url" :title="$row['book']->title" :seed="$row['book']->id" width="2.2rem" />
                <div class="dh-item-main"><b>{{ $row['book']->title }}</b><span>{{ $row['book']->authors->first()?->name }}</span></div>
                <span class="lib-badge lib-badge-blue">{{ $row['qty'] }}×</span>
            </div>
        @empty
            <div class="dh-empty">Belum ada data peminjaman.</div>
        @endforelse
    </section>
</div>

<section class="card">
    <div class="sec-head"><h3>{{ $u->isAdmin() ? 'Pintasan & pengguna sistem' : 'Pintasan' }}</h3></div>
    <div class="dh-shortcuts">
        <a href="{{ route('loans.create') }}"><x-icon name="swap" /><b>Peminjaman Baru</b><span>Catat peminjaman langsung</span></a>
        <a href="{{ route('loans.index', ['status' => 'pending']) }}"><x-icon name="clock" /><b>Pengajuan</b><span>{{ $stats['pending'] }} menunggu</span></a>
        <a href="{{ route('members.index') }}"><x-icon name="users" /><b>Anggota</b><span>{{ $stats['members'] }} terdaftar</span></a>
        <a href="{{ route('books.index') }}"><x-icon name="book" /><b>Buku</b><span>{{ $stats['books'] }} judul</span></a>
        @if ($u->isAdmin())
            <a href="{{ route('authors.index') }}"><x-icon name="layers" /><b>Data Master</b><span>Penulis, kategori, penerbit</span></a>
            <a href="{{ route('admin.dashboard') }}"><x-icon name="shield" /><b>Dashboard Admin</b><span>{{ array_sum($roleCounts) }} pengguna</span></a>
        @endif
    </div>
</section>
