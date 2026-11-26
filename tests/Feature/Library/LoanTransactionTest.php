<?php

use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Circulation\Services\LoanService;
use Modules\Membership\Models\Member;
use Modules\Shared\Exceptions\BookNotAvailableException;
use Modules\Shared\Exceptions\MemberCannotBorrowException;

beforeEach(function () {
    $this->member = Member::factory()->create();
    $this->service = app(LoanService::class);
});

it('peminjaman berhasil: stok berkurang, detail snapshot tersimpan, status anggota diperbarui', function () {
    $book = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);

    $loan = $this->service->borrow($this->member->id, [['book_id' => $book->id, 'quantity' => 2]]);

    expect($loan->details)->toHaveCount(1)
        ->and($loan->details->first()->book_title)->toBe($book->title)
        ->and($loan->details->first()->book_isbn)->toBe((string) $book->isbn)
        ->and($loan->member_name)->toBe($this->member->name)
        ->and($book->fresh()->stock)->toBe(3)
        ->and($this->member->fresh()->status)->toBe('borrowing')
        ->and($this->member->fresh()->active_loans_count)->toBe(2);
});

it('ROLLBACK: stok buku kedua tidak cukup membatalkan seluruh peminjaman', function () {
    $bookA = Book::factory()->create(['stock' => 5, 'total_stock' => 5]);
    $bookB = Book::factory()->create(['stock' => 1, 'total_stock' => 1]);

    expect(fn () => $this->service->borrow($this->member->id, [
        ['book_id' => $bookA->id, 'quantity' => 1],
        ['book_id' => $bookB->id, 'quantity' => 2], // melebihi stok
    ]))->toThrow(BookNotAvailableException::class);

    expect(Loan::count())->toBe(0)
        ->and(LoanDetail::count())->toBe(0)
        ->and($bookA->fresh()->stock)->toBe(5)   // stok buku pertama dikembalikan oleh rollback
        ->and($bookB->fresh()->stock)->toBe(1)
        ->and($this->member->fresh()->status)->toBe('active')
        ->and($this->member->fresh()->active_loans_count)->toBe(0);
});

it('anggota yang ditangguhkan tidak boleh meminjam', function () {
    $member = Member::factory()->suspended()->create();
    $book = Book::factory()->create(['stock' => 3, 'total_stock' => 3]);

    expect(fn () => $this->service->borrow($member->id, [['book_id' => $book->id]]))
        ->toThrow(MemberCannotBorrowException::class, 'ditangguhkan');

    expect($book->fresh()->stock)->toBe(3)->and(Loan::count())->toBe(0);
});

it('batas maksimal pinjaman sama untuk semua anggota (5 buku)', function () {
    $books = Book::factory()->count(6)->create(['stock' => 3, 'total_stock' => 3]);

    expect(fn () => $this->service->borrow(
        $this->member->id,
        $books->map(fn ($b) => ['book_id' => $b->id, 'quantity' => 1])->all()
    ))->toThrow(MemberCannotBorrowException::class, 'Batas pinjaman');

    expect(Loan::count())->toBe(0);
});

it('pengembalian terlambat menghitung denda, menambah stok, dan mengaktifkan kembali anggota', function () {
    $book = Book::factory()->create(['stock' => 4, 'total_stock' => 4]);
    $loan = $this->service->borrow($this->member->id, [['book_id' => $book->id, 'quantity' => 1]]);
    $loan->update(['due_at' => now()->subDays(4)]);

    $returned = $this->service->returnLoan($loan);

    expect($returned->status)->toBe('returned')
        ->and((float) $returned->total_fine)->toBe(4000.0)
        ->and($book->fresh()->stock)->toBe(4)
        ->and($this->member->fresh()->status)->toBe('active')
        ->and($this->member->fresh()->active_loans_count)->toBe(0);
});
