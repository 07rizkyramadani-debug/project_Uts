@php
    $isAdmin = auth()->user()->isAdmin();
    $st = request('status');
    $palettes = [['#6366f1', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5']];
    $tabs = [
        [null, 'Semua', 'all', ''], ['pending', 'Pengajuan', 'pending', 'warn'], ['borrowed', 'Dipinjam', 'borrowed', ''],
        ['overdue', 'Terlambat', 'overdue', 'bad'], ['returned', 'Dikembalikan', 'returned', ''], ['rejected', 'Ditolak', 'rejected', ''],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Peminjaman</h2>
                <p class="page-sub">Tinjau pengajuan, catat pengembalian, dan pantau seluruh sirkulasi buku.</p>
            </div>
            <div class="bk-hero-side">
                @if ($isAdmin)<a href="{{ route('loans.trashed') }}" class="btn btn-ghost">Peminjaman terhapus</a>@endif
                <a href="{{ route('loans.create') }}" class="btn btn-primary">+ Peminjaman Baru</a>
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')

        <div class="pg-tabs">
            @foreach ($tabs as [$value, $label, $key, $tone])
                <a href="{{ $value ? route('loans.index', ['status' => $value]) : route('loans.index') }}"
                    class="pg-tab {{ $tone }} {{ ($st ?: null) === $value ? 'on' : '' }}">{{ $label }} <b>{{ $summary[$key] }}</b></a>
            @endforeach
        </div>

        @if ($loans->isEmpty())
            <div class="bk-empty">
                <div class="bk-empty-ico">📋</div>
                <b>Belum ada peminjaman</b>
                <span>Peminjaman dan pengajuan anggota akan muncul di sini.</span>
            </div>
        @else
            <div class="ln-grid">
                @foreach ($loans as $loan)
                    @php
                        $tone = match (true) { $loan->isPending() => 'amber', $loan->isRejected() => 'gray', $loan->isReturned() => 'green', $loan->isOverdue() => 'red', default => 'blue' };
                        [$g1, $g2] = $palettes[($loan->member_id ?? 0) % count($palettes)];
                    @endphp
                    <article class="ln-card ln-{{ $tone }}">
                        <div class="ln-head">
                            <div>
                                <a href="{{ route('loans.show', $loan) }}" class="ln-no">{{ $loan->loan_number }}</a>
                                <div class="ln-when">diajukan {{ $loan->created_at->format('d M Y') }}</div>
                            </div>
                            <x-loan-status :loan="$loan" />
                        </div>

                        <div class="ln-member">
                            <span class="mb-avatar" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">{{ strtoupper(mb_substr($loan->member_name, 0, 1)) }}</span>
                            <span><b>{{ $loan->member_name }}</b><small>{{ $loan->member_number }}</small></span>
                        </div>

                        <ul class="ln-books">
                            @foreach ($loan->details as $detail)
                                <li>
                                    <x-mini-cover :url="$covers[$detail->book_id]['cover_url'] ?? null" :title="$detail->book_title" :seed="$detail->book_id" width="2.2rem" />
                                    <span>{{ $detail->book_title }}@if ($detail->quantity > 1) ×{{ $detail->quantity }}@endif<small>{{ $detail->book_isbn }}</small></span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="ln-meta">
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
                            <div><small>Denda</small><b>Rp {{ number_format($loan->isBorrowed() ? $loan->accruedFine() : $loan->total_fine, 0, ',', '.') }}</b>@if ($loan->isBorrowed() && $loan->accruedFine() > 0)<em class="late">berjalan</em>@endif</div>
                        </div>

                        <div class="ln-actions">
                            @if ($loan->isPending())
                                <form action="{{ route('loans.approve', $loan) }}" method="POST" onsubmit="return confirm('Setujui pengajuan ini? Stok akan dikurangi dan jatuh tempo mulai dihitung.');">@csrf
                                    <button type="submit" class="bk-act bk-act-ok">✓ Setujui</button>
                                </form>
                                <form action="{{ route('loans.reject', $loan) }}" method="POST" onsubmit="const r = prompt('Alasan penolakan (opsional):'); if (r === null) { return false; } this.rejection_reason.value = r; return true;">@csrf
                                    <input type="hidden" name="rejection_reason" value="">
                                    <button type="submit" class="bk-act bk-act-del">✕ Tolak</button>
                                </form>
                            @elseif ($loan->isBorrowed())
                                <form action="{{ route('loans.return', $loan) }}" method="POST" onsubmit="return confirm('Proses pengembalian?');">@csrf
                                    <button type="submit" class="bk-act bk-act-ok">↩ Kembalikan</button>
                                </form>
                            @endif
                            <a href="{{ route('loans.show', $loan) }}" class="bk-act bk-act-ghost">Detail</a>
                            @if ($isAdmin)
                                <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="del" onsubmit="return confirm('Hapus peminjaman ini (soft delete)?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bk-act bk-act-del" title="Hapus peminjaman">🗑</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $loans->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
