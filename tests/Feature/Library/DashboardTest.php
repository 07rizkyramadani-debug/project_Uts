<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;

beforeEach(function () {
    $this->service = app(LoanService::class);
    $this->account = User::factory()->create(['role' => 'user']);
    $this->member = Member::factory()->create(['user_id' => $this->account->id]);
});

it('dashboard user menampilkan total buku yang dipinjam, sedang dipinjam, dan pengajuan', function () {
    $books = Book::factory()->count(4)->create(['stock' => 5, 'total_stock' => 5]);

    // 1 pinjaman selesai (1 buku), 1 pinjaman berjalan (2 buku), 1 pengajuan menunggu
    $done = $this->service->borrow($this->member->id, [['book_id' => $books[0]->id]]);
    $this->service->returnLoan($done);
    $this->service->borrow($this->member->id, [['book_id' => $books[1]->id], ['book_id' => $books[2]->id]]);
    $this->service->submitRequest($this->member->id, [['book_id' => $books[3]->id]]);

    $this->actingAs($this->account)->get('/dashboard')
        ->assertOk()
        ->assertSee('Total Buku Dipinjam')
        ->assertSee('Rekomendasi buku tersedia')
        ->assertViewHas('myStats', fn ($s) => $s['total_books'] === 3 && $s['active_books'] === 2 && $s['pending'] === 1);
});

it('dashboard user menghitung kuantitas, bukan hanya jumlah transaksi', function () {
    $book = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);
    $this->service->borrow($this->member->id, [['book_id' => $book->id, 'quantity' => 2]]);

    $this->actingAs($this->account)->get('/dashboard')
        ->assertViewHas('myStats', fn ($s) => $s['total_books'] === 2 && $s['active_books'] === 2);
});

it('dashboard user tanpa data anggota otomatis aktif tanpa pesan "belum tertaut"', function () {
    $lonely = User::factory()->create(['role' => 'user']);

    $this->actingAs($lonely)->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Keanggotaan Anda tidak aktif');

    expect(Member::where('user_id', $lonely->id)->exists())->toBeTrue();
});

it('dashboard user yang anggotanya dihapus tetap tampil dengan pesan ramah', function () {
    $this->member->delete();

    $this->actingAs($this->account)->get('/dashboard')
        ->assertOk()
        ->assertSee('Keanggotaan Anda tidak aktif');
});

it('dashboard user tidak menampilkan pinjaman anggota lain', function () {
    $other = Member::factory()->create();
    $book = Book::factory()->create(['stock' => 5, 'total_stock' => 5, 'title' => 'Judul Rahasia Orang Lain']);
    $this->service->borrow($other->id, [['book_id' => $book->id]]);

    $this->actingAs($this->account)->get('/dashboard')->assertOk()->assertDontSee('Judul Rahasia Orang Lain');
});

it('dashboard pustakawan berisi ringkasan operasional tanpa data pengguna sistem', function () {
    $this->actingAs(User::factory()->create(['role' => 'pustakawan']))->get('/dashboard')
        ->assertOk()
        ->assertSee('Perlu tindakan')
        ->assertSee('Komposisi peminjaman')
        ->assertSee('Buku terpopuler')
        ->assertViewHas('roleCounts', null);
});

it('dashboard admin menambahkan pintasan data master dan jumlah pengguna per peran', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/dashboard')
        ->assertOk()
        ->assertSee('Data Master')
        ->assertViewHas('roleCounts', fn ($c) => isset($c['admin'], $c['pustakawan'], $c['user']));
});

it('halaman Dashboard Admin menampilkan komposisi pengguna dan pengguna terbaru', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Komposisi pengguna')
        ->assertSee('Pengguna terbaru');
});

describe('riwayat peminjaman pada Pinjaman Saya', function () {
    it('memisahkan peminjaman berjalan dari riwayat (dikembalikan dan ditolak)', function () {
        $b = Book::factory()->count(3)->create(['stock' => 5, 'total_stock' => 5]);

        $done = $this->service->borrow($this->member->id, [['book_id' => $b[0]->id]]);
        $this->service->returnLoan($done);
        $rejected = $this->service->submitRequest($this->member->id, [['book_id' => $b[1]->id]]);
        $this->service->rejectRequest($rejected, User::factory()->create(['role' => 'pustakawan'])->id, 'Stok dipesan dosen');
        $running = $this->service->borrow($this->member->id, [['book_id' => $b[2]->id]]);

        $this->actingAs($this->account)->get('/my-loans')
            ->assertOk()
            ->assertSee('Riwayat peminjaman')
            ->assertSee('Stok dipesan dosen')
            ->assertViewHas('active', fn ($a) => $a->pluck('id')->all() === [$running->id])
            ->assertViewHas('history', fn ($h) => $h->pluck('id')->sort()->values()->all() === collect([$done->id, $rejected->id])->sort()->values()->all());
    });

    it('riwayat dipaginasi 8 per halaman lewat parameter riwayat', function () {
        $book = Book::factory()->create(['stock' => 20, 'total_stock' => 20]);

        foreach (range(1, 10) as $i) {
            $this->service->returnLoan($this->service->borrow($this->member->id, [['book_id' => $book->id]]));
        }

        $this->actingAs($this->account)->get('/my-loans')
            ->assertViewHas('history', fn ($h) => $h->total() === 10 && $h->count() === 8);

        $this->actingAs($this->account)->get('/my-loans?riwayat=2')
            ->assertViewHas('history', fn ($h) => $h->currentPage() === 2 && $h->count() === 2);
    });

    it('menampilkan ringkasan total buku dipinjam dan total denda', function () {
        $book = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);
        $this->service->returnLoan($this->service->borrow($this->member->id, [['book_id' => $book->id]]));

        $this->actingAs($this->account)->get('/my-loans')
            ->assertSee('Total Buku Dipinjam')
            ->assertSee('Total Denda')
            ->assertViewHas('stats', fn ($s) => $s['total_books'] === 1 && $s['history'] === 1);
    });
});
