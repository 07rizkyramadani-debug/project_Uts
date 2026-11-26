<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookStockReport;
use Modules\Catalog\Requests\StoreBookStockReportRequest;

/**
 * Pustakawan melaporkan buku yang stoknya habis; admin melihat dan menandai laporan selesai.
 */
class BookStockReportController extends Controller
{
    /** Daftar laporan (admin): yang masih terbuka tampil lebih dulu. */
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [BookStockReport::STATUS_OPEN, BookStockReport::STATUS_RESOLVED], true)
            ? $request->query('status') : null;

        $reports = BookStockReport::with(['book.authors:id,name', 'reporter'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('status') // 'open' < 'resolved' secara alfabet
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $byStatus = BookStockReport::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = [
            'all' => (int) $byStatus->sum(),
            'open' => (int) ($byStatus[BookStockReport::STATUS_OPEN] ?? 0),
            'resolved' => (int) ($byStatus[BookStockReport::STATUS_RESOLVED] ?? 0),
        ];

        return view('library.books.stock_reports', compact('reports', 'summary', 'status'));
    }

    /** Pustakawan melaporkan buku yang stoknya habis. */
    public function store(StoreBookStockReportRequest $request, Book $book)
    {
        $data = $request->validated();

        if ($book->stock > 0) {
            return back()->withErrors(['stock' => "Stok buku '{$book->title}' masih tersedia, tidak perlu dilaporkan."]);
        }

        if (BookStockReport::open()->where('book_id', $book->id)->exists()) {
            return back()->withErrors(['stock' => "Buku '{$book->title}' sudah dilaporkan dan masih menunggu tindak lanjut admin."]);
        }

        BookStockReport::create([
            'book_id' => $book->id,
            'reported_by' => $request->user()->id,
            'note' => $data['note'] ?? null,
            'status' => BookStockReport::STATUS_OPEN,
        ]);

        return back()->with('success', "Stok habis buku '{$book->title}' sudah dilaporkan ke admin.");
    }

    /** Admin menandai laporan sudah ditangani. */
    public function resolve(BookStockReport $report)
    {
        $report->update([
            'status' => BookStockReport::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Laporan ditandai selesai.');
    }
}
