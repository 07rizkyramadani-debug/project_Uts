<?php

namespace Modules\Circulation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Requests\SubmitLoanRequest;
use Modules\Circulation\Services\LoanService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;

/**
 * Halaman "Pinjaman Saya" untuk user biasa. Anggota diambil lewat contract Membership
 * berdasarkan akun yang login, sehingga user hanya pernah melihat pinjamannya sendiri.
 */
class MyLoanController extends Controller
{
    private const HISTORY_PER_PAGE = 8;

    public function __construct(
        private LoanService $loanService,
        private MemberServiceInterface $memberService,
        private BookServiceInterface $bookService
    ) {}

    public function index(Request $request)
    {
        $member = $this->memberService->getMemberForUser($request->user()->id);

        if (! $member) {
            return view('library.loans.mine', ['member' => null]);
        }

        // Satu query untuk seluruh pinjaman anggota (+ detail), sisanya dipilah di memori:
        // jumlah query tetap sama berapa pun banyaknya pinjaman (tanpa N+1).
        $all = Loan::with('details')
            ->where('member_id', $member['id'])
            ->latest('created_at')
            ->get();

        $covers = $this->bookService->getCoverMap(
            $all->flatMap(fn (Loan $l) => $l->details->pluck('book_id'))->unique()->values()->all()
        );

        $status = $request->query('status');
        $filtered = in_array($status, ['pending', 'borrowed', 'overdue', 'returned', 'rejected'], true)
            ? $all->filter(fn (Loan $l) => match ($status) {
                'overdue' => $l->isOverdue(),
                default => $l->status === $status,
            })->values()
            : null;

        $active = $all->filter(fn (Loan $l) => $l->isPending() || $l->isBorrowed())
            ->sortBy(fn (Loan $l) => [$l->isOverdue() ? 0 : 1, $l->isPending() ? 1 : 0, $l->due_at?->timestamp ?? PHP_INT_MAX])
            ->values();

        $history = $this->paginate(
            $all->filter(fn (Loan $l) => $l->isReturned() || $l->isRejected())
                ->sortByDesc(fn (Loan $l) => ($l->returned_at ?? $l->decided_at ?? $l->created_at)->timestamp)
                ->values(),
            $request
        );

        $borrowedOrReturned = $all->filter(fn (Loan $l) => $l->isBorrowed() || $l->isReturned());
        $stats = [
            'total_books' => $borrowedOrReturned->sum(fn (Loan $l) => (int) $l->details->sum('quantity')),
            'active_books' => $all->filter->isBorrowed()->sum(fn (Loan $l) => (int) $l->details->sum('quantity')),
            'pending' => $all->filter->isPending()->count(),
            'overdue' => $all->filter->isOverdue()->count(),
            'fine' => $all->sum(fn (Loan $l) => $l->accruedFine()),
            'history' => $all->filter(fn (Loan $l) => $l->isReturned() || $l->isRejected())->count(),
        ];

        return view('library.loans.mine', compact('member', 'filtered', 'active', 'history', 'covers', 'stats'));
    }

    /** Pengajuan kini dilakukan dari daftar buku, jadi halaman form lama diarahkan ke sana. */
    public function create()
    {
        return redirect()->route('books.index');
    }

    public function store(SubmitLoanRequest $request)
    {
        $member = $this->memberService->getMemberForUser($request->user()->id);

        abort_if(! $member, 403, 'Keanggotaan Anda tidak aktif.');

        $data = $request->validated();

        $this->loanService->submitRequest($member['id'], $data['items'], $data['notes'] ?? null);

        return redirect()->route('my-loans.index')->with('success',
            'Pengajuan peminjaman terkirim. Pustakawan akan menyetujui atau menolaknya.');
    }

    /** Paginasi manual untuk riwayat (halaman diambil dari ?riwayat=). */
    private function paginate(Collection $items, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('riwayat', 1));

        return (new LengthAwarePaginator(
            $items->forPage($page, self::HISTORY_PER_PAGE)->values(),
            $items->count(),
            self::HISTORY_PER_PAGE,
            $page,
            ['path' => $request->url(), 'pageName' => 'riwayat']
        ))->withQueryString();
    }
}
