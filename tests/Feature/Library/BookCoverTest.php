<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Book;

it('URL sampul berupa alamat relatif sehingga tidak bergantung pada APP_URL', function () {
    config(['app.url' => 'http://alamat-yang-salah.test']);

    $book = Book::factory()->create(['cover_path' => 'covers/gerd.jpg']);

    expect($book->cover_url)->toBe('/book-covers/gerd.jpg');
});

it('buku tanpa sampul tidak punya URL sampul', function () {
    expect(Book::factory()->create(['cover_path' => null])->cover_url)->toBeNull();
});

it('gambar sampul bawaan seeder tetap bisa dibuka walau belum ada di storage atau belum storage:link', function () {
    Storage::fake('public');

    $this->get(route('books.cover', 'gerd.jpg'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('sampul yang diunggah ke storage disajikan lebih dulu', function () {
    Storage::fake('public');
    Storage::disk('public')->put('covers/unggahan.jpg', 'isi-gambar');

    $this->get(route('books.cover', 'unggahan.jpg'))->assertOk();
});

it('file yang tidak ada atau bukan gambar ditolak dengan 404', function () {
    Storage::fake('public');

    $this->get('/book-covers/tidak-ada.jpg')->assertNotFound();
    $this->get('/book-covers/rahasia.php')->assertNotFound();
    $this->get('/book-covers/..%2F.env')->assertNotFound();
});

it('user dan pustakawan melihat gambar sampul pada halaman koleksi buku', function (string $role) {
    Book::factory()->create(['title' => 'Sampul Uji Koleksi', 'cover_path' => 'covers/gerd.jpg']);

    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get('/books?q=Sampul Uji Koleksi')
        ->assertOk()
        ->assertSee('src="/book-covers/gerd.jpg"', false);
})->with(['user', 'pustakawan']);

it('halaman detail buku menampilkan gambar sampul', function () {
    $book = Book::factory()->create(['cover_path' => 'covers/gerd.jpg']);

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('books.show', $book))
        ->assertOk()
        ->assertSee('src="/book-covers/gerd.jpg"', false);
});
