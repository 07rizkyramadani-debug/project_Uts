<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\BookStockReport;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Catalog\Requests\StoreBookRequest;
use Modules\Catalog\Requests\UpdateBookRequest;
use Modules\Catalog\ValueObjects\Isbn;
use Modules\Shared\Contracts\LoanQueryInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Exceptions\ResourceInUseException;

class BookController extends Controller
{
    public function index(Request $request, MemberServiceInterface $memberService)
    {
        // Eager loading: authors, publisher, categories dimuat sekaligus (menghindari N+1)
        $books = Book::with(['authors', 'publisher', 'categories'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('category'), fn ($q) => $q->inCategory($request->integer('category')))
            ->when($request->boolean('available'), fn ($q) => $q->available())
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        // User biasa bisa mengajukan pinjam langsung dari daftar buku, asalkan keanggotaannya aktif (dibuat otomatis saat registrasi)
        $member = $request->user()->isUser() ? $memberService->getMemberForUser($request->user()->id) : null;

        // Pustakawan: buku mana di halaman ini yang stok habisnya sudah dilaporkan ke admin
        $reportedBookIds = ($request->user()->isPustakawan() || $request->user()->isAdmin())
            ? BookStockReport::open()->whereIn('book_id', $books->pluck('id'))->pluck('book_id')->all()
            : [];

        return view('library.books.index', compact('books', 'categories', 'member', 'reportedBookIds'));
    }

    public function show(Request $request, Book $book, MemberServiceInterface $memberService)
    {
        $book->load(['authors', 'publisher', 'categories.parent']);

        $member = $request->user()->isUser() ? $memberService->getMemberForUser($request->user()->id) : null;

        $hasOpenReport = $request->user()->isPustakawan()
            && BookStockReport::open()->where('book_id', $book->id)->exists();

        return view('library.books.show', compact('book', 'member', 'hasOpenReport'));
    }

    public function create()
    {
        return view('library.books.form', $this->formData(new Book));
    }

    public function store(StoreBookRequest $request)
    {
        $data = $request->validated();

        $coverPath = $request->hasFile('cover') ? $request->file('cover')->store('covers', 'public') : null;

        // Transaction: buku + relasi penulis/kategori (many-to-many) tersimpan semua atau tidak sama sekali
        try {
            DB::transaction(function () use ($data, $coverPath) {
                $book = Book::create([
                    ...collect($data)->except(['author_ids', 'category_ids', 'cover', 'remove_cover'])->all(),
                    'isbn' => new Isbn($data['isbn']),
                    'stock' => $data['total_stock'],
                    'is_active' => true,
                    'cover_path' => $coverPath,
                ]);

                $book->authors()->sync($data['author_ids']);
                $book->categories()->sync($this->categoryPivot($data['category_ids'] ?? []));
            });
        } catch (\Throwable $e) {
            // Rollback: file sampul yang sudah terunggah ikut dibersihkan
            $this->deleteCover($coverPath);

            throw $e;
        }

        return redirect()->route('books.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    public function edit(Book $book)
    {
        $book->load(['authors', 'categories']);

        return view('library.books.form', $this->formData($book));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $data = $request->validated();

        // Selisih total stok dipertahankan agar stok tersedia tetap konsisten
        $diff = $data['total_stock'] - $book->total_stock;

        DB::transaction(function () use ($book, $data, $diff, $request) {
            $coverPath = $book->cover_path;

            if ($request->hasFile('cover')) {
                $this->deleteCover($coverPath);
                $coverPath = $request->file('cover')->store('covers', 'public');
            } elseif ($request->boolean('remove_cover')) {
                $this->deleteCover($coverPath);
                $coverPath = null;
            }

            $book->update([
                ...collect($data)->except(['author_ids', 'category_ids', 'cover', 'remove_cover'])->all(),
                'isbn' => new Isbn($data['isbn']),
                'stock' => max(0, $book->stock + $diff),
                'cover_path' => $coverPath,
            ]);

            $book->authors()->sync($data['author_ids']);
            $book->categories()->sync($this->categoryPivot($data['category_ids'] ?? []));
        });

        // Stok sudah ditambah admin: laporan stok habis untuk buku ini otomatis selesai
        if ($book->fresh()->stock > 0) {
            BookStockReport::open()->where('book_id', $book->id)
                ->update(['status' => BookStockReport::STATUS_RESOLVED, 'resolved_at' => now()]);
        }

        return redirect()->route('books.index')->with('success', 'Buku berhasil diperbarui.');
    }

    /** Soft delete */
    public function destroy(Book $book, LoanQueryInterface $loans)
    {
        if ($loans->hasActiveLoansForBook($book->id)) {
            throw new ResourceInUseException("Buku '{$book->title}' tidak dapat dihapus karena masih dipinjam atau ada pengajuan peminjaman yang menunggu.");
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', 'Buku dipindahkan ke daftar terhapus
         (soft delete).');
    }

    /** Daftar buku yang di-soft delete (admin) */
    public function trashed()
    {
        $books = Book::onlyTrashed()->with(['authors', 'publisher'])->latest('deleted_at')->paginate(10);

        return view('library.books.trashed', compact('books'));
    }

    /** Restore buku (admin) */
    public function restore(int $id)
    {
        Book::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('books.trashed')->with('success', 'Buku berhasil dipulihkan.');
    }

    private function deleteCover(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function formData(Book $book): array
    {
        return [
            'book' => $book,
            'authors' => Author::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'parent_id']),
        ];
    }

    /** Siapkan data pivot (added_at) untuk relasi many-to-many buku-kategori. */
    private function categoryPivot(array $categoryIds): array
    {
        return collect($categoryIds)
            ->mapWithKeys(fn ($id) => [$id => ['added_at' => now()]])
            ->all();
    }
}
