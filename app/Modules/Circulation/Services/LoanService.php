<?php

namespace Modules\Circulation\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Circulation\ValueObjects\Denda;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Exceptions\BookNotAvailableException;
use Modules\Shared\Exceptions\MemberCannotBorrowException;

class LoanService
{
    public function __construct(
        private BookServiceInterface $bookService,
        private MemberServiceInterface $memberService
    ) {}

    /**
     * Proses peminjaman dalam satu transaction:
     *   1. validasi anggota (via contract Membership)
     *   2. buat record peminjaman + detail (snapshot buku)
     *   3. kurangi stok buku (via contract Catalog)
     *   4. perbarui status anggota
     * Jika ada langkah yang gagal (mis. stok tidak mencukupi) semua perubahan di-rollback.
     *
     * @param  array<int, array{book_id:int, quantity?:int}>  $items
     */
    public function borrow(int $memberId, array $items, ?int $loanDays = null, ?string $notes = null): Loan
    {
        $loan = DB::transaction(function () use ($memberId, $items, $loanDays, $notes) {
            $member = $this->memberService->getMemberForLoan($memberId);

            if (! $member) {
                throw new MemberCannotBorrowException('Anggota tidak ditemukan.');
            }

            $totalQuantity = (int) collect($items)->sum(fn ($i) => $i['quantity'] ?? 1);

            if ($items === [] || $totalQuantity < 1) {
                throw new MemberCannotBorrowException('Pilih minimal satu buku untuk dipinjam.');
            }

            if ($reason = $this->memberService->getBorrowBlockReason($memberId, $totalQuantity)) {
                throw new MemberCannotBorrowException($reason);
            }

            $loan = Loan::create([
                'member_id' => $member['id'],
                'member_number' => $member['member_number'],
                'member_name' => $member['name'],
                'loan_number' => 'LN-'.strtoupper(uniqid()),
                'borrowed_at' => now(),
                'due_at' => now()->addDays($loanDays ?? $member['loan_days']),
                'status' => Loan::STATUS_BORROWED,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $book = $this->takeStock((int) $item['book_id'], $quantity);

                LoanDetail::create([
                    'loan_id' => $loan->id,
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_isbn' => $book['isbn'],
                    'quantity' => $quantity,
                ]);
            }

            $this->memberService->markBorrowing($member['id'], $totalQuantity);

            return $loan->load('details');
        });

        // Domain event hanya ditembakkan setelah transaction berhasil di-commit
        event(new BookBorrowed($loan));

        return $loan;
    }

    /**
     * Proses pengembalian: hitung denda (Value Object Denda), tambah stok buku,
     * perbarui status anggota. Semua dalam satu transaction.
     */
    public function returnLoan(Loan $loan, ?CarbonInterface $returnedAt = null): Loan
    {
        return DB::transaction(function () use ($loan, $returnedAt) {
            $returnedAt = $returnedAt ?? now();
            $loan->loadMissing('details');

            if ($loan->isReturned()) {
                throw new MemberCannotBorrowException('Peminjaman ini sudah dikembalikan.');
            }

            if (! $loan->isBorrowed()) {
                throw new MemberCannotBorrowException('Hanya peminjaman yang sedang dipinjam yang dapat dikembalikan.');
            }

            $denda = Denda::hitung($loan->due_at, $returnedAt);
            $totalFine = 0.0;
            $totalQuantity = 0;

            foreach ($loan->details as $detail) {
                $fine = $denda->getAmount() * $detail->quantity;

                $detail->update([
                    'returned_at' => $returnedAt,
                    'fine_amount' => $fine,
                ]);

                $this->bookService->increaseStock($detail->book_id, $detail->quantity);

                $totalFine += $fine;
                $totalQuantity += $detail->quantity;
            }

            $this->memberService->markReturned($loan->member_id, $totalQuantity);

            $loan->update([
                'status' => Loan::STATUS_RETURNED,
                'returned_at' => $returnedAt,
                'total_fine' => $totalFine,
            ]);

            return $loan->fresh('details');
        });
    }

    /**
     * User mengajukan peminjaman: hanya mencatat pengajuan berstatus pending beserta snapshot buku.
     * Stok, jumlah pinjaman aktif, dan tanggal jatuh tempo BELUM berubah — itu terjadi saat disetujui.
     * Pengajuan yang masih menunggu ikut dihitung terhadap batas pinjaman agar tidak bisa menumpuk.
     *
     * @param  array<int, array{book_id:int, quantity?:int}>  $items
     */
    public function submitRequest(int $memberId, array $items, ?string $notes = null): Loan
    {
        return DB::transaction(function () use ($memberId, $items, $notes) {
            $member = $this->memberService->getMemberForLoan($memberId);

            if (! $member) {
                throw new MemberCannotBorrowException('Anggota tidak ditemukan.');
            }

            $totalQuantity = (int) collect($items)->sum(fn ($i) => $i['quantity'] ?? 1);

            if ($items === [] || $totalQuantity < 1) {
                throw new MemberCannotBorrowException('Pilih minimal satu buku untuk dipinjam.');
            }

            if ($reason = $this->memberService->getBorrowBlockReason($memberId, $totalQuantity)) {
                throw new MemberCannotBorrowException($reason);
            }

            $waiting = (int) LoanDetail::whereHas('loan', fn ($q) => $q->pending()->where('member_id', $memberId))->sum('quantity');

            if ($member['max_loans'] < $member['active_loans'] + $waiting + $totalQuantity) {
                throw new MemberCannotBorrowException(
                    "Pengajuan melebihi batas pinjaman (sedang meminjam {$member['active_loans']}, "
                    ."menunggu persetujuan {$waiting}, maksimal {$member['max_loans']})."
                );
            }

            $loan = Loan::create([
                'member_id' => $member['id'],
                'member_number' => $member['member_number'],
                'member_name' => $member['name'],
                'loan_number' => 'LN-'.strtoupper(uniqid()),
                'status' => Loan::STATUS_PENDING,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $book = $this->bookService->getBookForLoan((int) $item['book_id']);

                if (! $book) {
                    throw new BookNotAvailableException("(id {$item['book_id']})", 0, $quantity);
                }

                if (! $this->bookService->isAvailable($book['id'], $quantity)) {
                    throw new BookNotAvailableException($book['title'], $book['stock'], $quantity);
                }

                LoanDetail::create([
                    'loan_id' => $loan->id,
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_isbn' => $book['isbn'],
                    'quantity' => $quantity,
                ]);
            }

            return $loan->load('details');
        });
    }

    /**
     * Pustakawan menyetujui pengajuan. Langkahnya sama dengan peminjaman biasa: validasi anggota
     * ulang, kurangi stok, perbarui status anggota, isi tanggal pinjam & jatuh tempo — dalam satu
     * transaction. Bila gagal (mis. stok habis) pengajuan tetap pending.
     */
    public function approveRequest(Loan $loan, int $decidedBy): Loan
    {
        $loan = DB::transaction(function () use ($loan, $decidedBy) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);

            if (! $loan->isPending()) {
                throw new MemberCannotBorrowException('Pengajuan ini sudah diproses.');
            }

            $loan->load('details');
            $member = $this->memberService->getMemberForLoan($loan->member_id);

            if (! $member) {
                throw new MemberCannotBorrowException('Anggota tidak ditemukan.');
            }

            $totalQuantity = (int) $loan->details->sum('quantity');

            if ($reason = $this->memberService->getBorrowBlockReason($loan->member_id, $totalQuantity)) {
                throw new MemberCannotBorrowException($reason);
            }

            foreach ($loan->details as $detail) {
                $this->takeStock($detail->book_id, $detail->quantity);
            }

            $this->memberService->markBorrowing($member['id'], $totalQuantity);

            $loan->update([
                'status' => Loan::STATUS_BORROWED,
                'borrowed_at' => now(),
                'due_at' => now()->addDays($member['loan_days']),
                'decided_by' => $decidedBy,
                'decided_at' => now(),
            ]);

            return $loan->load('details');
        });

        event(new BookBorrowed($loan));

        return $loan;
    }

    /** Pustakawan menolak pengajuan; tidak ada perubahan stok maupun status anggota. */
    public function rejectRequest(Loan $loan, int $decidedBy, ?string $reason = null): Loan
    {
        return DB::transaction(function () use ($loan, $decidedBy, $reason) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);

            if (! $loan->isPending()) {
                throw new MemberCannotBorrowException('Pengajuan ini sudah diproses.');
            }

            $loan->update([
                'status' => Loan::STATUS_REJECTED,
                'decided_by' => $decidedBy,
                'decided_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $loan->load('details');
        });
    }

    /**
     * Cek ketersediaan lalu kurangi stok satu buku. Dipakai peminjaman langsung dan persetujuan.
     *
     * @return array{id:int, title:string, isbn:string, stock:int}
     */
    private function takeStock(int $bookId, int $quantity): array
    {
        $book = $this->bookService->getBookForLoan($bookId);

        if (! $book) {
            throw new BookNotAvailableException("(id {$bookId})", 0, $quantity);
        }

        if (! $this->bookService->isAvailable($book['id'], $quantity)
            || ! $this->bookService->reduceStock($book['id'], $quantity)) {
            throw new BookNotAvailableException($book['title'], $book['stock'], $quantity);
        }

        return $book;
    }
}
