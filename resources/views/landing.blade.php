<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpustakaan Digital — Temukan buku, ajukan pinjam, tanpa antre</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="description" content="Sistem Perpustakaan Digital: telusuri katalog, cek stok secara real-time, dan ajukan peminjaman buku secara online.">
    <script>document.documentElement.className = 'js';</script>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=1">
</head>
<body class="landing">

{{-- ============ NAVBAR ============ --}}
<header class="l-nav" id="nav">
    <div class="l-container l-nav-inner">
        <x-brand href="{{ route('home') }}" />
        <nav class="l-links" aria-label="Navigasi utama">
            <a href="#mulai">Mulai</a>
            <a href="#koleksi">Koleksi</a>
            <a href="#fitur">Fitur</a>
            <a href="#cara-kerja">Cara kerja</a>
            <a href="#faq">FAQ</a>
        </nav>
        <div class="l-actions">
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
        </div>
        <button type="button" class="l-burger" id="burger" aria-label="Buka menu"><x-icon name="menu" /></button>
    </div>
    <div class="l-mobile" id="mobile">
        <a href="#mulai">Mulai</a><a href="#koleksi">Koleksi</a><a href="#fitur">Fitur</a><a href="#cara-kerja">Cara kerja</a><a href="#faq">FAQ</a>
        <div class="row">
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
        </div>
    </div>
</header>

<main>
{{-- ============ HERO ============ --}}
<section class="l-hero">
    <div class="l-container l-hero-grid">
        <div>
            <span class="l-pill reveal"><i><x-icon name="bolt" /></i> Perpustakaan modern, kini serba online</span>
            <h1 class="reveal" style="--d:.08s">Temukan buku, ajukan pinjam, <em class="l-grad">tanpa antre.</em></h1>
            <p class="lead reveal" style="--d:.16s">
                Telusuri katalog lengkap, cek ketersediaan stok secara langsung, lalu ajukan peminjaman dalam satu klik.
                Pustakawan yang mengurus sisanya.
            </p>
            <div class="l-cta reveal" style="--d:.24s">
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang <x-icon name="arrow" /></a>
                <a href="#koleksi" class="btn btn-ghost">Lihat Koleksi</a>
            </div>
            <div class="l-trust reveal" style="--d:.32s">
                <span><x-icon name="check" /> Maksimal {{ $maxLoans }} buku</span>
                <span><x-icon name="check" /> Pinjam {{ $loanDays }} hari</span>
                <span><x-icon name="check" /> Stok diperbarui real-time</span>
            </div>
        </div>

        <div class="l-visual reveal" style="--d:.2s" aria-hidden="true">
            <span class="l-shape l-ring"></span>
            <span class="l-shape l-hex"></span>
            <span class="l-shape l-pent"></span>
            <svg class="l-shape l-star" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>
            <span class="l-shape l-sq"></span>
            <svg class="l-shape l-rocket" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9s-2.1-.8-2.9-.1zM12 15l-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22 22 0 0 1-4 2zM9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/></svg>

            <div class="l-fan">
                @foreach ($heroBooks as $i => $book)
                    <div class="l-card l-c{{ $i + 1 }}"><x-book-cover :book="$book" /></div>
                @endforeach
            </div>

            <div class="l-float l-f1"><span class="ico"><x-icon name="check" /></span><span>Pengajuan disetujui<small>Silakan ambil di perpustakaan</small></span></div>
            <div class="l-float l-f2"><span class="ico"><x-icon name="book" /></span><span>Stok tersedia<small>5 dari 5 eksemplar</small></span></div>
            <div class="l-float l-f3"><span class="ico"><x-icon name="clock" /></span><span>Jatuh tempo {{ $loanDays }} hari<small>Pengingat otomatis di akun Anda</small></span></div>
        </div>
    </div>
</section>

{{-- ============ STATISTIK ============ --}}
<section class="l-stats">
    <div class="l-container">
        <div class="l-stats-card reveal">
            <div class="l-stat"><b data-count="{{ $stats['books'] }}">{{ $stats['books'] }}</b><span>Judul buku</span></div>
            <div class="l-stat"><b data-count="{{ $stats['authors'] }}">{{ $stats['authors'] }}</b><span>Penulis</span></div>
            <div class="l-stat"><b data-count="{{ $stats['categories'] }}">{{ $stats['categories'] }}</b><span>Kategori</span></div>
            <div class="l-stat"><b data-count="{{ $stats['members'] }}">{{ $stats['members'] }}</b><span>Anggota aktif</span></div>
        </div>
    </div>
</section>

{{-- ============ MULAI (PERAN) ============ --}}
<section class="l-section" id="mulai">
    <div class="l-container l-start">
        <div class="reveal">
            <span class="l-eyebrow">Mulai dari sini</span>
            <h2 class="l-title">Satu sistem, <span class="l-grad">tiga peran</span> yang saling melengkapi.</h2>
            <p class="l-sub">Setiap pengguna melihat hanya yang ia butuhkan. Anggota mencari dan meminjam, pustakawan melayani sirkulasi, admin mengelola seluruh koleksi.</p>
            <p class="l-have">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
        <div class="l-roles">
            <a href="{{ route('register') }}" class="l-role reveal" style="--d:.05s">
                <span class="ico"><x-icon name="user" /></span>
                <span><h3>Anggota</h3><p>Telusuri katalog dan ajukan peminjaman buku</p></span>
                <span class="go"><x-icon name="arrow" /></span>
            </a>
            <a href="{{ route('login') }}" class="l-role reveal" style="--d:.12s; --t:#d1fae5; --tc:#047857">
                <span class="ico"><x-icon name="swap" /></span>
                <span><h3>Pustakawan</h3><p>Setujui pengajuan, catat peminjaman dan pengembalian</p></span>
                <span class="go"><x-icon name="arrow" /></span>
            </a>
            <a href="{{ route('login') }}" class="l-role reveal" style="--d:.19s; --t:#ffe4e6; --tc:#be123c">
                <span class="ico"><x-icon name="shield" /></span>
                <span><h3>Admin</h3><p>Kelola buku, anggota, dan data master perpustakaan</p></span>
                <span class="go"><x-icon name="arrow" /></span>
            </a>
        </div>
    </div>
</section>

{{-- ============ KOLEKSI ============ --}}
<section class="l-showcase" id="koleksi">
    <div class="l-container l-head reveal">
        <span class="l-eyebrow">Koleksi kami</span>
        <h2 class="l-title">Ribuan halaman, <span class="l-grad">siap dijelajahi.</span></h2>
        <p class="l-sub">Dari teknologi dan sains hingga hukum, bahasa, dan kesehatan. Daftar untuk melihat detail dan mengajukan peminjaman.</p>
    </div>

    @if ($rowA->isNotEmpty())
        <div class="l-mq" aria-label="Contoh sampul buku koleksi">
            <div class="l-track">
                @foreach ([1, 2] as $pass)
                    @foreach ($rowA as $book)
                        <div class="l-item" @if ($pass === 2) aria-hidden="true" @endif><x-book-cover :book="$book" /><span class="tt">{{ $book->title }}</span></div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <div class="l-mq rev" aria-hidden="true">
            <div class="l-track">
                @foreach ([1, 2] as $pass)
                    @foreach ($rowB as $book)
                        <div class="l-item"><x-book-cover :book="$book" /><span class="tt">{{ $book->title }}</span></div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <div class="l-container">
        <div class="l-cats reveal">
            @foreach ($categories as $category)
                <span>{{ $category->name }}<b>{{ $category->books_count }}</b></span>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FITUR ============ --}}
<section class="l-section" id="fitur">
    <div class="l-container">
        <div class="l-head reveal">
            <span class="l-eyebrow">Fitur unggulan</span>
            <h2 class="l-title">Semua yang dibutuhkan perpustakaan, <span class="l-grad">dalam satu tempat.</span></h2>
            <p class="l-sub">Dibangun dengan arsitektur Modular Monolith Laravel 13 yang rapi, aman, dan mudah dikembangkan.</p>
        </div>
        <div class="l-features">
            @php
                $features = [
                    ['search', 'Katalog pintar', 'Cari berdasarkan judul, ISBN, atau penulis, lalu saring per kategori dan ketersediaan stok.', '#e0e7ff', '#4338ca'],
                    ['swap', 'Ajukan pinjam online', 'Ajukan peminjaman dari halaman buku. Pustakawan menyetujui atau menolak, statusnya terlihat di akun Anda.', '#d1fae5', '#047857'],
                    ['coin', 'Stok dan denda otomatis', 'Stok berkurang dan bertambah sendiri. Denda keterlambatan Rp1.000 per hari dihitung saat pengembalian.', '#fef3c7', '#b45309'],
                    ['shield', 'Akses sesuai peran', 'Admin, pustakawan, dan anggota punya hak akses berbeda yang dijaga di setiap halaman.', '#ffe4e6', '#be123c'],
                    ['layers', 'Data master lengkap', 'Kelola penulis, kategori bertingkat, dan penerbit lengkap dengan perlindungan data yang masih dipakai.', '#e0f2fe', '#0369a1'],
                    ['lock', 'Aman dan siap terintegrasi', 'Login Laravel Sanctum, API bertoken, pembatasan laju permintaan, dan pengaturan CORS.', '#ede9fe', '#6d28d9'],
                ];
            @endphp
            @foreach ($features as $i => [$icon, $title, $text, $tone, $ink])
                <article class="l-feat reveal" style="--d:{{ ($i % 3) * .08 }}s; --t:{{ $tone }}; --tc:{{ $ink }}">
                    <span class="ico"><x-icon :name="$icon" /></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CARA KERJA ============ --}}
<section class="l-section" id="cara-kerja">
    <div class="l-container">
        <div class="l-head reveal">
            <span class="l-eyebrow">Cara kerja</span>
            <h2 class="l-title">Meminjam buku semudah <span class="l-grad">empat langkah.</span></h2>
        </div>
        <div class="l-steps">
            @foreach ([
                ['Daftar akun', 'Buat akun gratis dalam hitungan detik, lalu masuk.'],
                ['Cari buku', 'Telusuri katalog dan cek stok yang tersedia.'],
                ['Ajukan pinjam', 'Klik "Ajukan Pinjam". Pustakawan akan memprosesnya.'],
                ['Ambil dan kembalikan', 'Pinjam hingga '.$maxLoans.' buku selama '.$loanDays.' hari, lalu kembalikan tepat waktu.'],
            ] as $i => [$title, $text])
                <div class="l-step reveal" style="--d:{{ $i * .1 }}s">
                    <div class="n">{{ $i + 1 }}</div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="l-section" id="faq">
    <div class="l-container">
        <div class="l-head reveal">
            <span class="l-eyebrow">Pertanyaan umum</span>
            <h2 class="l-title">Masih ada yang <span class="l-grad">ingin ditanyakan?</span></h2>
        </div>
        <div class="l-faq reveal">
            <details open>
                <summary>Siapa yang bisa meminjam buku?</summary>
                <p>Siapa pun dapat mendaftar akun. Setiap akun yang mendaftar langsung menjadi anggota aktif, sehingga bisa mengajukan peminjaman tanpa menunggu verifikasi manual.</p>
            </details>
            <details>
                <summary>Berapa buku dan berapa lama saya boleh meminjam?</summary>
                <p>Batas pinjam mengikuti jenis keanggotaan (Umum, Mahasiswa, atau Dosen). Anggota Umum dapat meminjam maksimal {{ $maxLoans }} buku dalam satu waktu dengan masa pinjam {{ $loanDays }} hari sejak disetujui pustakawan; jenis lain mendapat batas yang lebih longgar.</p>
            </details>
            <details>
                <summary>Bagaimana denda keterlambatan dihitung?</summary>
                <p>Denda Rp1.000 per hari keterlambatan, dihitung otomatis saat buku dikembalikan dan tercatat di halaman Pinjaman Saya.</p>
            </details>
            <details>
                <summary>Bagaimana saya tahu pengajuan sudah disetujui?</summary>
                <p>Buka menu Pinjaman Saya. Statusnya berubah dari "Menunggu persetujuan" menjadi "Dipinjam" atau "Ditolak" beserta alasannya.</p>
            </details>
            <details>
                <summary>Apakah data saya aman?</summary>
                <p>Login memakai Laravel Sanctum, hak akses dibatasi per peran, dan permintaan login dibatasi lajunya untuk mencegah penyalahgunaan.</p>
            </details>
        </div>
    </div>
</section>

{{-- ============ CTA AKHIR ============ --}}
<section class="l-final">
    <div class="l-container">
        <div class="l-final-card reveal">
            <h2>Siap mulai membaca?</h2>
            <p>Bergabung sekarang dan temukan buku berikutnya yang akan mengubah cara Anda belajar.</p>
            <div class="l-cta">
                <a href="{{ route('register') }}" class="btn btn-white">Daftar Gratis <x-icon name="arrow" /></a>
                <a href="{{ route('login') }}" class="btn btn-glass">Saya Sudah Punya Akun</a>
            </div>
        </div>
    </div>
</section>
</main>

{{-- ============ FOOTER ============ --}}
<footer class="l-footer">
    <div class="l-container">
        <div class="l-foot-grid">
            <div>
                <x-brand href="{{ route('home') }}" />
                <p>Sistem manajemen perpustakaan digital berbasis Modular Monolith Laravel 13.</p>
            </div>
            <div>
                <h4>Jelajahi</h4>
                <ul><li><a href="#koleksi">Koleksi</a></li><li><a href="#fitur">Fitur</a></li><li><a href="#cara-kerja">Cara kerja</a></li><li><a href="#faq">FAQ</a></li></ul>
            </div>
            <div>
                <h4>Akun</h4>
                <ul><li><a href="{{ route('login') }}">Masuk</a></li><li><a href="{{ route('register') }}">Daftar</a></li></ul>
            </div>
        </div>
        <div class="l-copy"><span>&copy; {{ now()->year }} Perpustakaan Digital</span><span>UTS Backend Development &middot; Universitas AMIKOM Yogyakarta</span></div>
    </div>
</footer>

<script>
(function () {
    var nav = document.getElementById('nav'), mobile = document.getElementById('mobile');
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 12); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
    document.getElementById('burger').addEventListener('click', function () { mobile.classList.toggle('open'); });
    mobile.addEventListener('click', function (e) { if (e.target.tagName === 'A') mobile.classList.remove('open'); });
    document.documentElement.style.scrollBehavior = 'smooth';

    // Munculkan elemen saat di-scroll
    var items = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    } else { items.forEach(function (el) { el.classList.add('in'); }); }

    // Hitung naik pada angka statistik
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduce || target === 0) return;
        el.textContent = '0';
        var seen = new IntersectionObserver(function (es) {
            if (!es[0].isIntersecting) return; seen.disconnect();
            var t0 = performance.now(), dur = 1400;
            (function tick(t) {
                var p = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * e).toLocaleString('id-ID');
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        }, { threshold: 0.6 });
        seen.observe(el);
    });
})();
</script>
</body>
</html>
