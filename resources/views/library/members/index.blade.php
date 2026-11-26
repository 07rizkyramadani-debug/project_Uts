@php
    $isAdmin = auth()->user()->isAdmin();
    $palettes = [['#6366f1', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5']];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Anggota</h2>
                <p class="page-sub">Data anggota perpustakaan, status keanggotaan, dan kuota pinjam mereka.</p>
            </div>
            <div class="bk-hero-side">
                @if ($isAdmin)<a href="{{ route('membership-types.index') }}" class="btn btn-ghost">Jenis keanggotaan</a><a href="{{ route('members.trashed') }}" class="btn btn-ghost">Anggota terhapus</a>@endif
                <a href="{{ route('members.create') }}" class="btn btn-primary">+ Tambah Anggota</a>
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')

        <div class="pg-summary">
            <div class="pg-sum"><small>Total anggota</small><b>{{ $summary['all'] }}</b></div>
            <div class="pg-sum tone-g"><small>Aktif</small><b>{{ $summary['active'] }}</b></div>
            <div class="pg-sum tone-b"><small>Sedang meminjam</small><b>{{ $summary['borrowing'] }}</b></div>
            <div class="pg-sum tone-r"><small>Ditangguhkan</small><b>{{ $summary['suspended'] }}</b></div>
        </div>

        <form method="GET" class="bk-filter">
            <div class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, nomor anggota, atau email...">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>

        @if ($members->isEmpty())
            <div class="bk-empty">
                <div class="bk-empty-ico">👥</div>
                <b>Anggota tidak ditemukan</b>
                <span>Coba kata kunci lain atau tambahkan anggota baru.</span>
            </div>
        @else
            <div class="mb-grid">
                @foreach ($members as $member)
                    @php
                        [$g1, $g2] = $palettes[$member->id % count($palettes)];
                        $max = $member->maxLoans();
                        $pct = min(100, round($member->active_loans_count / $max * 100));
                        $tone = $pct >= 100 ? 'out' : ($pct >= 60 ? 'low' : 'ok');
                    @endphp
                    <article class="mb-card">
                        <div class="mb-top">
                            <span class="mb-avatar" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">{{ strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                            <div class="mb-id">
                                <h3 title="{{ $member->name }}">{{ $member->name }}</h3>
                                <span class="mb-no">{{ $member->member_number }}@if ($member->membershipType) · {{ $member->membershipType->name }}@endif</span>
                            </div>
                            @if ($member->status === 'suspended') <span class="lib-badge lib-badge-red">Ditangguhkan</span>
                            @elseif ($member->status === 'borrowing') <span class="lib-badge lib-badge-blue">Meminjam</span>
                            @else <span class="lib-badge lib-badge-green">Aktif</span> @endif
                        </div>

                        <div class="mb-info">
                            <div><i>✉</i><span>{{ $member->email }}</span></div>
                            <div><i>☎</i><span>{{ $member->phone ?: 'Nomor telepon belum diisi' }}</span></div>
                            @if ($member->user)
                                <div class="mb-link"><i>🔗</i><span>Akun: <b>{{ $member->user->email }}</b></span></div>
                            @endif
                        </div>

                        <div>
                            <div class="mb-quota-row"><span>Pinjaman aktif</span><b>{{ $member->active_loans_count }} / {{ $max }}</b></div>
                            <div class="bk-bar"><i class="bk-bar-{{ $tone }}" style="width: {{ $pct }}%"></i></div>
                        </div>

                        <div class="mb-actions">
                            @if ($isAdmin)
                                <a href="{{ route('members.edit', $member) }}" class="bk-act bk-act-edit">✎ Edit</a>
                                <form action="{{ route('members.destroy', $member) }}" method="POST" onsubmit="return confirm('Hapus anggota {{ e($member->name) }} (soft delete)?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bk-act bk-act-del">🗑 Hapus</button>
                                </form>
                            @else
                                @if ($member->status === 'suspended')
                                    <span class="bk-act bk-act-ghost full" style="cursor:not-allowed;opacity:.7">Tidak dapat meminjam</span>
                                @else
                                    <a href="{{ route('loans.create', ['member_id' => $member->id]) }}" class="bk-act bk-act-go full">📚 Pinjamkan Buku</a>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $members->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
