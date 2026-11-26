<?php

use App\Models\User;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookStockReport;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
    $this->user = User::factory()->create(['role' => 'user']);
});

it('pustakawan melihat tombol lapor (bukan tombol pinjam) pada buku yang stoknya habis', function () {
    $book = Book::factory()->outOfStock()->create();

    $this->actingAs($this->pustakawan)->get('/books')
        ->assertOk()
        ->assertSee('Laporkan ke Admin')
        ->assertDontSee('📚 Pinjam')
        ->assertDontSee('Belum bisa pinjam')
        ->assertDontSee('belum tertaut');
});

it('pustakawan melaporkan buku yang stoknya habis ke admin', function () {
    $book = Book::factory()->outOfStock()->create();

    $this->actingAs($this->pustakawan)
        ->post("/books/{$book->id}/report-stock", ['note' => 'Banyak peminat'])
        ->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('book_stock_reports', [
        'book_id' => $book->id,
        'reported_by' => $this->pustakawan->id,
        'status' => 'open',
    ]);
});

it('buku yang stoknya masih ada tidak bisa dilaporkan', function () {
    $book = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);

    $this->actingAs($this->pustakawan)
        ->post("/books/{$book->id}/report-stock")
        ->assertSessionHasErrors('stock');

    expect(BookStockReport::count())->toBe(0);
});

it('laporan yang masih terbuka untuk buku yang sama tidak dibuat dua kali', function () {
    $book = Book::factory()->outOfStock()->create();

    $this->actingAs($this->pustakawan)->post("/books/{$book->id}/report-stock");
    $this->actingAs($this->pustakawan)->post("/books/{$book->id}/report-stock")
        ->assertSessionHasErrors('stock');

    expect(BookStockReport::count())->toBe(1);
});

it('user dan admin tidak boleh melapor, hanya pustakawan', function () {
    $book = Book::factory()->outOfStock()->create();

    $this->actingAs($this->user)->post("/books/{$book->id}/report-stock")->assertForbidden();
    $this->actingAs($this->admin)->post("/books/{$book->id}/report-stock")->assertForbidden();
});

it('pustakawan tetap tidak bisa mengajukan pinjam', function () {
    $book = Book::factory()->create();

    $this->actingAs($this->pustakawan)->post('/my-loans', [
        'items' => [['book_id' => $book->id, 'quantity' => 1]],
    ])->assertForbidden();
});

it('admin melihat dan menandai laporan selesai, pustakawan tidak boleh membuka daftarnya', function () {
    $book = Book::factory()->outOfStock()->create();
    $report = BookStockReport::create([
        'book_id' => $book->id,
        'reported_by' => $this->pustakawan->id,
        'status' => 'open',
    ]);

    $this->actingAs($this->pustakawan)->get('/stock-reports')->assertForbidden();
    $this->actingAs($this->user)->get('/stock-reports')->assertForbidden();

    $this->actingAs($this->admin)->get('/stock-reports')
        ->assertOk()->assertSee($book->title);

    $this->actingAs($this->admin)->patch("/stock-reports/{$report->id}/resolve")
        ->assertRedirect()->assertSessionHas('success');

    expect($report->fresh()->status)->toBe('resolved')
        ->and($report->fresh()->resolved_at)->not->toBeNull();
});

it('laporan otomatis selesai saat admin menambah stok buku', function () {
    $book = Book::factory()->outOfStock()->create(['total_stock' => 2]);
    $report = BookStockReport::create([
        'book_id' => $book->id,
        'reported_by' => $this->pustakawan->id,
        'status' => 'open',
    ]);

    $this->actingAs($this->admin)->put("/books/{$book->id}", [
        'title' => $book->title,
        'isbn' => (string) $book->isbn,
        'total_stock' => 5,
        'author_ids' => [\Modules\Catalog\Models\Author::factory()->create()->id],
    ])->assertRedirect('/books');

    expect($report->fresh()->status)->toBe('resolved');
});
