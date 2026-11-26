<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Membership\Models\Member;

/**
 * Halaman awal publik (landing page) sebelum login/register.
 * Berada di lapisan aplikasi (composition root), sehingga boleh merangkum data beberapa modul.
 */
class WelcomeController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $covered = Book::with('authors:id,name')
            ->whereNotNull('cover_path')
            ->orderBy('id')
            ->limit(24)
            ->get();

        $showcase = $covered->slice(5)->values();

        return view('landing', [
            'heroBooks' => $covered->take(5),
            'rowA' => $showcase->take(10),
            'rowB' => $showcase->slice(10)->values()->whenEmpty(fn () => $showcase->reverse()->values()),
            'categories' => Category::withCount('books')->orderByDesc('books_count')->limit(12)->get(['id', 'name']),
            'stats' => [
                'books' => Book::count(),
                'authors' => Author::count(),
                'categories' => Category::count(),
                'members' => Member::count(),
            ],
            'loanDays' => Member::LOAN_DAYS,
            'maxLoans' => Member::MAX_LOANS,
        ]);
    }
}
