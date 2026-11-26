@php
    $u = auth()->user();
    $first = \Illuminate\Support\Str::of($u->name)->explode(' ')->first();
@endphp
<section class="dh-hero">
    <div class="dh-hero-text">
        <span class="dh-chip"><x-icon :name="$mode === 'staff' ? 'shield' : 'user'" /> {{ ucfirst($u->role) }}</span>
        <h1>{{ $greeting }}, {{ $first }}!</h1>
        <p>
            @if ($u->isAdmin())
                Pantau seluruh aktivitas perpustakaan, kelola koleksi, anggota, dan data master dalam satu tampilan.
            @elseif ($u->isPustakawan())
                Layani sirkulasi hari ini: tinjau pengajuan, catat peminjaman, dan proses pengembalian buku.
            @elseif ($member ?? null)
                Anggota <b>{{ $member['member_number'] }}</b>. Telusuri koleksi, ajukan pinjaman, dan pantau jatuh tempo Anda di sini.
            @else
                Telusuri koleksi dan cek ketersediaan buku. Hubungi pustakawan untuk menautkan akun Anda ke data anggota.
            @endif
        </p>
        <div class="dh-actions">
            <a href="{{ route('books.index') }}" class="btn btn-light">📚 Telusuri Katalog</a>
            @if ($mode === 'staff')
                <a href="{{ route('loans.create') }}" class="btn btn-outline-light">+ Peminjaman Baru</a>
                @if ($u->isAdmin())<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light">Dashboard Admin</a>@endif
            @elseif ($member ?? null)
                <a href="{{ route('my-loans.index') }}" class="btn btn-outline-light">Pinjaman Saya</a>
            @endif
        </div>
    </div>
    <div class="dh-hero-covers" aria-hidden="true">
        @foreach ($heroBooks as $i => $book)
            <div class="dh-cover dh-cover-{{ $i + 1 }}"><x-book-cover :book="$book" /></div>
        @endforeach
    </div>
</section>
