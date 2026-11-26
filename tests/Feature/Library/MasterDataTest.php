<?php

use App\Models\User;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pustakawan = User::factory()->create(['role' => 'pustakawan']);
});

it('hanya admin yang boleh mengelola penulis, kategori, dan penerbit', function () {
    foreach (['/authors/create', '/categories/create', '/publishers/create', '/publishers'] as $url) {
        $this->actingAs($this->pustakawan)->get($url)->assertForbidden();
        $this->actingAs($this->admin)->get($url)->assertOk();
    }

    // Daftar penulis dan kategori tetap bisa dilihat semua user yang login
    $this->actingAs($this->pustakawan)->get('/authors')->assertOk();
    $this->actingAs($this->pustakawan)->get('/categories')->assertOk();
    $this->actingAs($this->pustakawan)->post('/authors', ['name' => 'X'])->assertForbidden();
});

it('admin menambah, mengubah, dan menghapus penulis', function () {
    $this->actingAs($this->admin)->post('/authors', ['name' => 'Tere Liye', 'bio' => 'Novelis'])
        ->assertRedirect('/authors')->assertSessionHas('success');

    $author = Author::where('name', 'Tere Liye')->firstOrFail();

    $this->actingAs($this->admin)->put("/authors/{$author->id}", ['name' => 'Darwis Tere Liye'])->assertRedirect('/authors');
    expect($author->fresh()->name)->toBe('Darwis Tere Liye');

    $this->actingAs($this->admin)->delete("/authors/{$author->id}")->assertRedirect('/authors');
    expect(Author::count())->toBe(0);
});

it('validasi penulis: nama wajib diisi dengan pesan informatif', function () {
    $this->actingAs($this->admin)->post('/authors', ['name' => ''])
        ->assertSessionHasErrors(['name' => 'Nama penulis wajib diisi.']);
});

it('penulis yang masih dipakai buku tidak bisa dihapus (Custom Exception)', function () {
    $author = Author::factory()->create();
    Book::factory()->create()->authors()->attach($author);

    $this->actingAs($this->admin)->from('/authors')->delete("/authors/{$author->id}")
        ->assertRedirect('/authors')
        ->assertSessionHasErrors('delete');

    expect(Author::count())->toBe(1);
});

it('penerbit: tambah, nama unik, dan tidak bisa dihapus bila dipakai buku', function () {
    $this->actingAs($this->admin)->post('/publishers', ['name' => 'Penerbit Baru', 'city' => 'Bandung'])->assertRedirect('/publishers');
    $this->actingAs($this->admin)->post('/publishers', ['name' => 'Penerbit Baru'])
        ->assertSessionHasErrors(['name' => 'Penerbit dengan nama tersebut sudah ada.']);

    $publisher = Publisher::where('name', 'Penerbit Baru')->firstOrFail();
    Book::factory()->create(['publisher_id' => $publisher->id]);

    $this->actingAs($this->admin)->from('/publishers')->delete("/publishers/{$publisher->id}")->assertSessionHasErrors('delete');
    expect(Publisher::count())->toBe(1);
});

it('kategori: slug dibuat otomatis, induk harus kategori utama, dan guard hapus', function () {
    $this->actingAs($this->admin)->post('/categories', ['name' => 'Teknologi Informasi'])->assertRedirect('/categories');
    $root = Category::where('slug', 'teknologi-informasi')->firstOrFail();

    $this->actingAs($this->admin)->post('/categories', ['name' => 'Basis Data', 'parent_id' => $root->id])->assertRedirect('/categories');
    $child = Category::where('slug', 'basis-data')->firstOrFail();
    expect($child->parent_id)->toBe($root->id);

    // sub-kategori tidak boleh menjadi induk (maksimal dua tingkat)
    $this->actingAs($this->admin)->post('/categories', ['name' => 'Indeks', 'parent_id' => $child->id])
        ->assertSessionHasErrors('parent_id');

    // nama yang menghasilkan slug sama ditolak
    $this->actingAs($this->admin)->post('/categories', ['name' => 'basis data'])->assertSessionHasErrors('slug');

    // kategori yang punya sub-kategori tidak bisa dihapus
    $this->actingAs($this->admin)->from('/categories')->delete("/categories/{$root->id}")->assertSessionHasErrors('delete');
    expect(Category::count())->toBe(2);

    // sub-kategori kosong bisa dihapus
    $this->actingAs($this->admin)->delete("/categories/{$child->id}")->assertRedirect('/categories');
    expect(Category::count())->toBe(1);
});
