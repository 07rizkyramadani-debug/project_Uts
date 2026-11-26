<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->book = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);
    $this->member = Member::factory()->create();
    $this->service = app(LoanService::class);
});

it('buku yang sedang dipinjam tidak bisa dihapus dan boleh dihapus setelah dikembalikan', function () {
    $loan = $this->service->borrow($this->member->id, [['book_id' => $this->book->id]]);

    $this->actingAs($this->admin)->from('/books')->delete("/books/{$this->book->id}")
        ->assertRedirect('/books')
        ->assertSessionHasErrors('delete');
    expect(Book::find($this->book->id))->not->toBeNull();

    $this->service->returnLoan($loan);

    $this->actingAs($this->admin)->delete("/books/{$this->book->id}")->assertSessionHasNoErrors();
    expect(Book::find($this->book->id))->toBeNull();
});

it('buku yang masih ada di pengajuan pending juga tidak bisa dihapus', function () {
    $this->service->submitRequest($this->member->id, [['book_id' => $this->book->id]]);

    $this->actingAs($this->admin)->from('/books')->delete("/books/{$this->book->id}")->assertSessionHasErrors('delete');
    expect(Book::find($this->book->id))->not->toBeNull();
});

it('anggota dengan pinjaman aktif tidak bisa dihapus dan boleh dihapus setelah semua dikembalikan', function () {
    $loan = $this->service->borrow($this->member->id, [['book_id' => $this->book->id]]);

    $this->actingAs($this->admin)->from('/members')->delete("/members/{$this->member->id}")
        ->assertRedirect('/members')
        ->assertSessionHasErrors('delete');
    expect(Member::find($this->member->id))->not->toBeNull();

    $this->service->returnLoan($loan);

    $this->actingAs($this->admin)->delete("/members/{$this->member->id}")->assertSessionHasNoErrors();
    expect(Member::find($this->member->id))->toBeNull();
});

it('tabel roles dan role_user yang tidak dipakai sudah dihapus', function () {
    expect(Schema::hasTable('roles'))->toBeFalse()->and(Schema::hasTable('role_user'))->toBeFalse();
});

it('dashboard pustakawan menampilkan pengajuan pending pada peminjaman terbaru', function () {
    $loan = $this->service->submitRequest($this->member->id, [['book_id' => $this->book->id]]);

    $this->actingAs(User::factory()->create(['role' => 'pustakawan']))->get('/dashboard')
        ->assertOk()
        ->assertSee($loan->loan_number);
});
