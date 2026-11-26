<?php

use App\Models\User;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
    $this->user = User::factory()->create(['role' => 'user']);
});

describe('tambah cepat penerbit, penulis, dan kategori dari form buku', function () {
    it('form tambah buku admin menyediakan tombol tambah cepat untuk ketiganya', function () {
        $this->actingAs($this->admin)->get('/books/create')
            ->assertOk()
            ->assertSee('Penerbit baru')
            ->assertSee('Penulis baru')
            ->assertSee('Kategori baru')
            ->assertSee(route('publishers.store'), false)
            ->assertSee(route('authors.store'), false)
            ->assertSee(route('categories.store'), false);
    });

    it('admin membuat penulis, penerbit, dan kategori lewat AJAX dan mendapat id serta nama', function () {
        $this->actingAs($this->admin)->postJson('/authors', ['name' => 'Penulis Baru'])
            ->assertCreated()->assertJsonStructure(['id', 'name'])->assertJsonPath('name', 'Penulis Baru');

        $this->actingAs($this->admin)->postJson('/publishers', ['name' => 'Penerbit Baru', 'city' => 'Bandung'])
            ->assertCreated()->assertJsonPath('name', 'Penerbit Baru');

        $root = Category::factory()->create(['parent_id' => null]);
        $this->actingAs($this->admin)->postJson('/categories', ['name' => 'Sub Kategori Baru', 'parent_id' => $root->id])
            ->assertCreated()->assertJsonPath('parent_id', $root->id);

        expect(Author::where('name', 'Penulis Baru')->exists())->toBeTrue()
            ->and(Publisher::where('name', 'Penerbit Baru')->value('city'))->toBe('Bandung')
            ->and(Category::where('slug', 'sub-kategori-baru')->exists())->toBeTrue();
    });

    it('validasi AJAX mengembalikan 422 dengan pesan yang bisa ditampilkan di form', function () {
        Publisher::factory()->create(['name' => 'Sudah Ada']);

        $this->actingAs($this->admin)->postJson('/publishers', ['name' => 'Sudah Ada'])
            ->assertUnprocessable()->assertJsonPath('errors.name.0', 'Penerbit dengan nama tersebut sudah ada.');

        $this->actingAs($this->admin)->postJson('/authors', ['name' => ''])
            ->assertUnprocessable()->assertJsonPath('errors.name.0', 'Nama penulis wajib diisi.');
    });

    it('pustakawan dan user tidak boleh menambah data master lewat AJAX', function () {
        foreach ([$this->pustakawan, $this->user] as $account) {
            $this->actingAs($account)->postJson('/authors', ['name' => 'X'])->assertForbidden();
            $this->actingAs($account)->postJson('/publishers', ['name' => 'X'])->assertForbidden();
            $this->actingAs($account)->postJson('/categories', ['name' => 'X'])->assertForbidden();
        }
    });

    it('buku bisa disimpan dengan penerbit, penulis, dan kategori yang baru dibuat lewat AJAX', function () {
        $author = $this->actingAs($this->admin)->postJson('/authors', ['name' => 'Penulis Inline'])->json('id');
        $publisher = $this->actingAs($this->admin)->postJson('/publishers', ['name' => 'Penerbit Inline'])->json('id');
        $category = $this->actingAs($this->admin)->postJson('/categories', ['name' => 'Kategori Inline'])->json('id');

        $this->actingAs($this->admin)->post('/books', [
            'title' => 'Buku Dengan Data Baru', 'isbn' => '978-0-306-40615-7', 'total_stock' => 2,
            'publisher_id' => $publisher, 'author_ids' => [$author], 'category_ids' => [$category],
        ])->assertRedirect('/books');

        $book = Book::where('title', 'Buku Dengan Data Baru')->firstOrFail();
        expect($book->publisher_id)->toBe($publisher)
            ->and($book->authors->pluck('id')->all())->toBe([$author])
            ->and($book->categories->pluck('id')->all())->toBe([$category]);
    });
});

describe('daftar buku admin berbentuk kartu', function () {
    it('admin melihat kartu dengan aksi Edit dan Hapus, bukan tabel', function () {
        $book = Book::factory()->create();

        $this->actingAs($this->admin)->get('/books')
            ->assertOk()
            ->assertDontSee('<table', false)
            ->assertSee('bk-grid', false)
            ->assertSee(route('books.edit', $book), false)
            ->assertSee('🗑 Hapus')
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('Tambah Buku');
    });

    it('pustakawan dan user tidak melihat aksi Edit maupun Hapus pada kartu buku', function () {
        $book = Book::factory()->create();

        foreach ([$this->pustakawan, $this->user] as $account) {
            $this->actingAs($account)->get('/books')
                ->assertOk()
                ->assertDontSee(route('books.edit', $book), false)
                ->assertDontSee('🗑 Hapus')
                ->assertDontSee('name="_method" value="DELETE"', false)
                ->assertDontSee('Tambah Buku');
        }
    });

    it('admin tidak melihat pesan "akun belum tertaut" milik user biasa', function () {
        Book::factory()->create();

        $this->actingAs($this->admin)->get('/books')->assertDontSee('belum tertaut');
    });
});

describe('halaman anggota berbentuk kartu', function () {
    it('admin melihat kartu anggota dengan aksi Edit dan Hapus', function () {
        $member = Member::factory()->create();

        $this->actingAs($this->admin)->get('/members')
            ->assertOk()
            ->assertDontSee('<table', false)
            ->assertSee('mb-card', false)
            ->assertSee($member->member_number)
            ->assertSee(route('members.edit', $member), false)
            ->assertSee(route('members.destroy', $member), false)
            ->assertViewHas('summary', fn ($s) => $s['all'] === 1);
    });

    it('pustakawan melihat tombol Pinjamkan Buku, bukan Edit atau Hapus', function () {
        $member = Member::factory()->create(['status' => 'active']);

        $this->actingAs($this->pustakawan)->get('/members')
            ->assertOk()
            ->assertSee('Pinjamkan Buku')
            ->assertSee(route('loans.create', ['member_id' => $member->id]), false)
            ->assertDontSee(route('members.edit', $member), false)
            ->assertDontSee(route('members.destroy', $member), false);
    });

    it('form peminjaman memilih anggota dari parameter member_id', function () {
        $member = Member::factory()->create();
        Book::factory()->create();

        $this->actingAs($this->pustakawan)->get(route('loans.create', ['member_id' => $member->id]))
            ->assertOk()
            ->assertSee('value="'.$member->id.'" selected', false);
    });
});

describe('halaman peminjaman berbentuk kartu', function () {
    beforeEach(function () {
        $this->service = app(LoanService::class);
        $this->member = Member::factory()->create();
        $this->books = Book::factory()->count(3)->create(['stock' => 5, 'total_stock' => 5]);
    });

    it('menampilkan kartu dengan aksi sesuai status dan tanpa tabel', function () {
        $running = $this->service->borrow($this->member->id, [['book_id' => $this->books[0]->id]]);
        $pending = $this->service->submitRequest($this->member->id, [['book_id' => $this->books[1]->id]]);

        $this->actingAs($this->pustakawan)->get('/loans')
            ->assertOk()
            ->assertDontSee('<table', false)
            ->assertSee('ln-card', false)
            ->assertSee($running->loan_number)
            ->assertSee(route('loans.approve', $pending), false)
            ->assertSee(route('loans.reject', $pending), false)
            ->assertSee(route('loans.return', $running), false)
            ->assertViewHas('summary', fn ($s) => $s['all'] === 2 && $s['pending'] === 1 && $s['borrowed'] === 1)
            ->assertViewHas('covers');
    });

    it('pengajuan menunggu ditampilkan paling awal agar cepat ditinjau', function () {
        $running = $this->service->borrow($this->member->id, [['book_id' => $this->books[0]->id]]);
        $pending = $this->service->submitRequest($this->member->id, [['book_id' => $this->books[1]->id]]);

        $this->actingAs($this->pustakawan)->get('/loans')
            ->assertViewHas('loans', fn ($p) => $p->first()->id === $pending->id);
    });

    it('hanya admin yang melihat tombol hapus peminjaman', function () {
        $loan = $this->service->borrow($this->member->id, [['book_id' => $this->books[0]->id]]);

        // URL hapus sama dengan URL detail, jadi yang dicek adalah form DELETE dan tombolnya
        $this->actingAs($this->admin)->get('/loans')
            ->assertSee('title="Hapus peminjaman"', false)
            ->assertSee('name="_method" value="DELETE"', false);
        $this->actingAs($this->pustakawan)->get('/loans')
            ->assertDontSee('title="Hapus peminjaman"', false)
            ->assertDontSee('name="_method" value="DELETE"', false);
    });

    it('filter status tetap bekerja dan tab menampilkan jumlahnya', function () {
        $this->service->borrow($this->member->id, [['book_id' => $this->books[0]->id]]);
        $this->service->submitRequest($this->member->id, [['book_id' => $this->books[1]->id]]);

        $this->actingAs($this->pustakawan)->get('/loans?status=pending')
            ->assertOk()
            ->assertViewHas('loans', fn ($p) => $p->total() === 1);
    });
});
