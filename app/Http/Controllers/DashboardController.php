<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanDetail;
use Modules\Membership\Models\Member;
use Modules\Shared\Contracts\MemberServiceInterface;

/**
 * Dashboard ringkasan untuk semua peran. Berada di lapisan aplikasi (composition root)
 * sehingga boleh merangkum data dari beberapa modul; antar-modul tetap tidak saling akses.
 */
class DashboardController extends Controller
{
    public function index(MemberServiceInterface $members): View
    {
        $user = auth()->user();
        $now = now('Asia/Jakarta');

        $greeting = match (true) {
            $now->hour < 11 => 'Selamat pagi',
            $now->hour < 15 => 'Selamat siang',
            $now->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $base = [
            'greeting' => $greeting,
            'today' => $now->locale('id')->translatedFormat('l, d F Y'),
            'heroBooks' => Book::with('authors:id,name')->whereNotNull('cover_path')->latest('id')->limit(3)->get(),
        ];

        return $user->canManageCirculation()
            ? view('dashboard', $base + ['mode' => 'staff'] + $this->staffData($user))
            : view('dashboard', $base + ['mode' => 'member'] + $this->memberData($user, $members));
    }

    /** Data dashboard admin & pustakawan. */
    private function staffData(User $user): array
    {
        $active = Loan::borrowed()->count();
        $overdue = Loan::overdue()->count();
        $pending = Loan::pending()->count();
        $returned = Loan::where('status', Loan::STATUS_RETURNED)->count();
        $rejected = Loan::where('status', Loan::STATUS_REJECTED)->count();

        // Aktivitas 7 hari terakhir (pengajuan + peminjaman baru per hari)
        $perDay = Loan::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->pluck('created_at')
            ->countBy(fn ($d) => $d->format('Y-m-d'));
        $week = collect(range(6, 0))->map(fn ($i) => [
            'label' => now()->subDays($i)->locale('id')->translatedFormat('D'),
            'count' => (int) ($perDay[now()->subDays($i)->format('Y-m-d')] ?? 0),
        ]);

        $popular = LoanDetail::query()
            ->selectRaw('book_id, sum(quantity) as qty')
            ->whereHas('loan', fn ($q) => $q->whereIn('status', [Loan::STATUS_BORROWED, Loan::STATUS_RETURNED]))
            ->groupBy('book_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();
        $popularBooks = Book::withTrashed()->with('authors:id,name')->whereIn('id', $popular->pluck('book_id'))->get()->keyBy('id');

        return [
            'stats' => [
                'books' => Book::count(),
                'available' => Book::where('stock', '>', 0)->count(),
                'members' => Member::count(),
                'active' => $active,
                'pending' => $pending,
                'overdue' => $overdue,
            ],
            'donut' => [
                ['label' => 'Dipinjam', 'count' => max(0, $active - $overdue), 'color' => '#6366f1'],
                ['label' => 'Terlambat', 'count' => $overdue, 'color' => '#ef4444'],
                ['label' => 'Menunggu', 'count' => $pending, 'color' => '#f59e0b'],
                ['label' => 'Dikembalikan', 'count' => $returned, 'color' => '#10b981'],
                ['label' => 'Ditolak', 'count' => $rejected, 'color' => '#94a3b8'],
            ],
            'week' => $week,
            'pendingList' => Loan::pending()->with('details')->oldest('created_at')->limit(5)->get(),
            'overdueList' => Loan::overdue()->with('details')->orderBy('due_at')->limit(5)->get(),
            'recentLoans' => Loan::latest('created_at')->limit(6)->get(),
            'popular' => $popular->map(fn ($row) => ['book' => $popularBooks[$row->book_id] ?? null, 'qty' => (int) $row->qty])->filter(fn ($r) => $r['book']),
            'roleCounts' => $user->isAdmin() ? [
                'admin' => User::where('role', 'admin')->count(),
                'pustakawan' => User::where('role', 'pustakawan')->count(),
                'user' => User::where('role', 'user')->count(),
            ] : null,
        ];
    }

    /** Data dashboard user biasa (anggota). */
    private function memberData(User $user, MemberServiceInterface $members): array
    {
        $member = $members->getMemberForUser($user->id);

        $recommended = Book::with('authors:id,name')
            ->where('stock', '>', 0)
            ->whereNotNull('cover_path')
            ->latest('id')
            ->limit(7)
            ->get();

        if (! $member) {
            return ['member' => null, 'recommended' => $recommended];
        }

        $loans = Loan::with('details')->where('member_id', $member['id'])->latest('created_at')->get();
        $covers = Book::withTrashed()
            ->whereIn('id', $loans->flatMap(fn (Loan $l) => $l->details->pluck('book_id'))->unique())
            ->get(['id', 'title', 'cover_path'])
            ->keyBy('id');

        $qty = fn (Collection $c) => (int) $c->sum(fn (Loan $l) => $l->details->sum('quantity'));
        $running = $loans->filter(fn (Loan $l) => $l->isBorrowed() || $l->isReturned());

        return [
            'member' => $member,
            'covers' => $covers,
            'recommended' => $recommended,
            'myStats' => [
                'total_books' => $qty($running),
                'active_books' => $qty($loans->filter->isBorrowed()),
                'pending' => $loans->filter->isPending()->count(),
                'overdue' => $loans->filter->isOverdue()->count(),
                'fine' => $loans->sum(fn (Loan $l) => $l->accruedFine()),
            ],
            'activeLoans' => $loans->filter(fn (Loan $l) => $l->isBorrowed() || $l->isPending())
                ->sortBy(fn (Loan $l) => [$l->isOverdue() ? 0 : 1, $l->isPending() ? 1 : 0, $l->due_at?->timestamp ?? PHP_INT_MAX])
                ->take(4)->values(),
            'historyLoans' => $loans->filter(fn (Loan $l) => $l->isReturned() || $l->isRejected())->take(4)->values(),
        ];
    }
}
