<?php

use App\Models\User;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookStockReport;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
});

describe('Data Master berbentuk kartu', function () {
    it('halaman penulis, kategori, dan penerbit tidak memakai tabel', function () {
        Author::factory()->create();
        Category::factory()->create();
        Publisher::factory()->create();

        foreach (['/authors' => 'ms-card', '/categories' => 'cg-card', '/publishers' => 'ms-card'] as $url => $class) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertDontSee('<table', false)
                ->assertSee($class, false);
        }
    });

    it('tab data master menampilkan jumlah masing-masing data', function () {
        Author::factory()->count(3)->create();
        Category::factory()->count(2)->create();
        Publisher::factory()->count(4)->create();

        $this->actingAs($this->admin)->get('/authors')
            ->assertOk()
            ->assertSee('Penulis <b>3</b>', false)
            ->assertSee('Kategori <b>2</b>', false)
            ->assertSee('Penerbit <b>4</b>', false);
    });

    it('penulis: pencarian nama bekerja dan ringkasan dihitung benar', function () {
        $withBook = Author::factory()->create(['name' => 'Penulis Berbuku']);
        Book::factory()->create()->authors()->attach($withBook);
        Author::factory()->create(['name' => 'Penulis Kosong']);

        $this->actingAs($this->admin)->get('/authors?q=Berbuku')
            ->assertOk()
            ->assertSee('Penulis Berbuku')
            ->assertDontSee('Penulis Kosong')
            ->assertViewHas('summary', fn ($s) => $s['all'] === 2 && $s['with_books'] === 1 && $s['without_books'] === 1);
    });

    it('penerbit: pencarian bisa memakai nama maupun kota', function () {
        Publisher::factory()->create(['name' => 'Penerbit Satu', 'city' => 'Bandung']);
        Publisher::factory()->create(['name' => 'Penerbit Dua', 'city' => 'Surabaya']);

        $this->actingAs($this->admin)->get('/publishers?q=Surabaya')
            ->assertOk()->assertSee('Penerbit Dua')->assertDontSee('Penerbit Satu');
        $this->actingAs($this->admin)->get('/publishers?q=Satu')
            ->assertOk()->assertSee('Penerbit Satu')->assertDontSee('Penerbit Dua');
    });

    it('aksi Edit dan Hapus hanya tampil untuk admin, penerbit hanya untuk admin', function () {
        $author = Author::factory()->create();

        $this->actingAs($this->admin)->get('/authors')
            ->assertSee(route('authors.edit', $author), false)->assertSee('🗑 Hapus');

        $this->actingAs($this->pustakawan)->get('/authors')
            ->assertOk()->assertDontSee(route('authors.edit', $author), false)->assertDontSee('🗑 Hapus');

        $this->actingAs($this->pustakawan)->get('/publishers')->assertForbidden();
    });

    it('kategori: sub-kategori tampil di dalam kartu induk dan form sub-kategori memilih induk otomatis', function () {
        $root = Category::factory()->create(['name' => 'Teknologi', 'parent_id' => null]);
        Category::factory()->create(['name' => 'Jaringan Komputer', 'parent_id' => $root->id]);

        $this->actingAs($this->admin)->get('/categories')
            ->assertOk()
            ->assertSee('Jaringan Komputer')
            ->assertSee(route('categories.create', ['parent_id' => $root->id]), false)
            ->assertViewHas('summary', fn ($s) => $s['roots'] === 1 && $s['subs'] === 1);

        $this->actingAs($this->admin)->get(route('categories.create', ['parent_id' => $root->id]))
            ->assertOk()->assertSee('value="'.$root->id.'" selected', false);
    });
});

describe('Laporan Stok berbentuk kartu', function () {
    beforeEach(function () {
        $this->open = BookStockReport::create(['book_id' => Book::factory()->create(['title' => 'Buku Laporan Terbuka'])->id, 'reported_by' => $this->pustakawan->id, 'note' => 'Sudah habis', 'status' => BookStockReport::STATUS_OPEN]);
        $this->done = BookStockReport::create(['book_id' => Book::factory()->create(['title' => 'Buku Laporan Selesai'])->id, 'reported_by' => $this->pustakawan->id, 'status' => BookStockReport::STATUS_RESOLVED, 'resolved_by' => $this->admin->id, 'resolved_at' => now()]);
    });

    it('menampilkan kartu tanpa tabel lengkap dengan ringkasan', function () {
        $this->actingAs($this->admin)->get('/stock-reports')
            ->assertOk()
            ->assertDontSee('<table', false)
            ->assertSee('sr-card', false)
            ->assertSee('Buku Laporan Terbuka')
            ->assertSee('Buku Laporan Selesai')
            ->assertSee('Sudah habis')
            ->assertViewHas('summary', fn ($s) => $s['all'] === 2 && $s['open'] === 1 && $s['resolved'] === 1);
    });

    it('tab status menyaring laporan menunggu dan selesai', function () {
        $this->actingAs($this->admin)->get('/stock-reports?status=open')
            ->assertSee('Buku Laporan Terbuka')->assertDontSee('Buku Laporan Selesai');

        $this->actingAs($this->admin)->get('/stock-reports?status=resolved')
            ->assertSee('Buku Laporan Selesai')->assertDontSee('Buku Laporan Terbuka');
    });

    it('laporan menunggu punya aksi Tambah Stok dan Tandai Selesai, laporan selesai tidak', function () {
        $page = $this->actingAs($this->admin)->get('/stock-reports?status=open');
        $page->assertSee('Tambah Stok')->assertSee(route('stock-reports.resolve', $this->open), false);

        $this->actingAs($this->admin)->get('/stock-reports?status=resolved')
            ->assertSee('Diselesaikan')->assertDontSee('Tandai Selesai');
    });

    it('hanya admin yang boleh membuka halaman laporan stok', function () {
        $this->actingAs($this->pustakawan)->get('/stock-reports')->assertForbidden();
    });
});

it('animasi mencakup kartu data master dan laporan stok', function () {
    $css = file_get_contents(public_path('css/motion.css'));
    $js = file_get_contents(public_path('js/motion.js'));

    expect($css)->toContain('.ms-grid')->toContain('.cg-grid')->toContain('.sr-grid')
        ->and($js)->toContain('.ms-card')->toContain('.cg-card')->toContain('.sr-card');
});
