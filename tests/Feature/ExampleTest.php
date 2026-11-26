<?php

use App\Models\User;
use Modules\Catalog\Models\Book;

it('menampilkan halaman awal publik untuk tamu', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Temukan buku, ajukan pinjam')
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false);
});

it('halaman awal menampilkan sampul buku dan statistik koleksi', function () {
    Book::factory()->count(7)->create(['cover_path' => 'covers/gerd.jpg']);

    $this->get('/')
        ->assertOk()
        ->assertSee('/book-covers/gerd.jpg', false)
        ->assertSee('data-count="7"', false);
});

it('halaman awal tetap tampil walau koleksi masih kosong', function () {
    $this->get('/')->assertOk()->assertSee('Siap mulai membaca?');
});

it('mengarahkan pengguna yang sudah login dari halaman awal ke dashboard', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
});
