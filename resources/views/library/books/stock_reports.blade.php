@php
    $palettes = [['#6366f1', '#7c3aed'], ['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#e11d48', '#f97316'], ['#7c3aed', '#db2777'], ['#0f766e', '#2563eb'], ['#b45309', '#dc2626'], ['#334155', '#4f46e5']];
    $tabs = [[null, 'Semua', 'all', ''], ['open', 'Menunggu', 'open', 'warn'], ['resolved', 'Selesai', 'resolved', '']];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Laporan Stok Habis</h2>
                <p class="page-sub">Laporan dari pustakawan untuk buku yang stoknya habis. Tambah stok lalu tandai selesai.</p>
            </div>
            <div class="bk-hero-side"><a href="{{ route('books.index') }}" class="btn btn-ghost">📚 Ke Daftar Buku</a></div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')

        <div class="pg-summary">
            <div class="pg-sum tone-a"><small>Menunggu ditangani</small><b>{{ $summary['open'] }}</b></div>
            <div class="pg-sum tone-g"><small>Sudah selesai</small><b>{{ $summary['resolved'] }}</b></div>
            <div class="pg-sum"><small>Total laporan</small><b>{{ $summary['all'] }}</b></div>
        </div>

        <div class="pg-tabs">
            @foreach ($tabs as [$value, $label, $key, $tone])
                <a href="{{ $value ? route('stock-reports.index', ['status' => $value]) : route('stock-reports.index') }}"
                    class="pg-tab {{ $tone }} {{ $status === $value ? 'on' : '' }}">{{ $label }} <b>{{ $summary[$key] }}</b></a>
            @endforeach
        </div>

        @if ($reports->isEmpty())
            <div class="bk-empty"><div class="bk-empty-ico">✅</div><b>Belum ada laporan stok habis</b><span>Laporan dari pustakawan akan muncul di sini.</span></div>
        @else
            <div class="sr-grid">
                @foreach ($reports as $report)
                    @php
                        $book = $report->book; $alive = $book && ! $book->trashed();
                        [$g1, $g2] = $palettes[($book?->id ?? 0) % count($palettes)];
                        $stock = $book?->stock ?? 0;
                    @endphp
                    <article class="sr-card {{ $report->isOpen() ? 'sr-open' : 'sr-done' }}">
                        <div class="sr-top">
                            <x-mini-cover :url="$book?->cover_url" :title="$book?->title ?? '-'" :seed="$book?->id ?? 0" width="3.4rem" />
                            <div class="sr-book">
                                @if ($alive)
                                    <a class="sr-title" href="{{ route('books.show', $book) }}">{{ $book->title }}</a>
                                @else
                                    <span class="sr-title">{{ $book?->title ?? '-' }} <small>(dihapus)</small></span>
                                @endif
                                <span class="sr-author">{{ $book?->authors->pluck('name')->join(', ') ?: '-' }}</span>
                            </div>
                            <span class="lib-badge {{ $report->isOpen() ? 'lib-badge-amber' : 'lib-badge-green' }}">{{ $report->isOpen() ? 'Menunggu' : 'Selesai' }}</span>
                        </div>

                        <div class="sr-stock">
                            <span>Stok saat ini</span>
                            <b class="lib-badge {{ $stock === 0 ? 'lib-badge-red sr-zero' : 'lib-badge-green' }}">{{ $stock }}</b>
                        </div>

                        <div class="sr-who">
                            <span class="mb-avatar" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">{{ strtoupper(mb_substr($report->reporter?->name ?? '?', 0, 1)) }}</span>
                            <span><b>{{ $report->reporter?->name ?? '-' }}</b><small>{{ $report->created_at->format('d M Y H:i') }} · {{ $report->created_at->locale('id')->diffForHumans() }}</small></span>
                        </div>

                        <blockquote class="sr-note {{ $report->note ? '' : 'off' }}">{{ $report->note ?: 'Tidak ada catatan.' }}</blockquote>

                        <div class="sr-actions">
                            @if ($report->isOpen())
                                @if ($alive)<a href="{{ route('books.edit', $report->book) }}" class="bk-act bk-act-edit">✎ Tambah Stok</a>@endif
                                <form action="{{ route('stock-reports.resolve', $report) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="bk-act bk-act-ok">✓ Tandai Selesai</button>
                                </form>
                            @else
                                <span class="sr-resolved">✓ Diselesaikan {{ $report->resolved_at?->format('d M Y') }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $reports->links('library.pagination') }}</div>
        @endif
    </div>
</x-app-layout>
