<?php

use App\Models\User;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('admin menambah buku: relasi many-to-many penulis dan kategori (pivot) tersimpan', function () {
    $authors = Author::factory()->count(2)->create();
    $category = Category::factory()->create();
    $isbn = (string) Book::factory()->make()->isbn;

    $this->actingAs($this->admin)->post('/books', [
        'title' => 'Pemrograman Backend Laravel',
        'isbn' => $isbn,
        'total_stock' => 4,
        'author_ids' => $authors->pluck('id')->all(),
        'category_ids' => [$category->id],
    ])->assertRedirect('/books')->assertSessionHas('success');

    $book = Book::where('title', 'Pemrograman Backend Laravel')->first();

    expect($book->stock)->toBe(4)
        ->and($book->authors)->toHaveCount(2)
        ->and($book->categories->first()->pivot->added_at)->not->toBeNull();
});

it('validasi gagal: ISBN salah dan penulis kosong menampilkan pesan error', function () {
    $this->actingAs($this->admin)->post('/books', [
        'title' => '',
        'isbn' => '123',
        'total_stock' => -1,
        'author_ids' => [],
    ])->assertSessionHasErrors(['title', 'isbn', 'total_stock', 'author_ids']);

    expect(Book::count())->toBe(0);
});

it('ROLLBACK: kegagalan saat menyimpan relasi membatalkan pembuatan buku', function () {
    $author = Author::factory()->create();
    $isbn = (string) Book::factory()->make()->isbn;

    // Memaksa kegagalan setelah buku dibuat: event "created" melempar exception di dalam transaction
    Book::created(fn () => throw new RuntimeException('simulasi kegagalan'));

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($this->admin)->post('/books', [
        'title' => 'Buku Gagal',
        'isbn' => $isbn,
        'total_stock' => 1,
        'author_ids' => [$author->id],
    ]))->toThrow(RuntimeException::class);

    expect(Book::where('title', 'Buku Gagal')->exists())->toBeFalse();
});
