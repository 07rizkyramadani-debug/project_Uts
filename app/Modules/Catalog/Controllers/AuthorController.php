<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Requests\StoreAuthorRequest;
use Modules\Shared\Exceptions\ResourceInUseException;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        // Eager loading judul buku tiap penulis dalam 1 query tambahan
        $authors = Author::with('books:id,title')
            ->withCount('books')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'all' => Author::count(),
            'with_books' => Author::has('books')->count(),
            'without_books' => Author::doesntHave('books')->count(),
        ];

        return view('library.authors.index', compact('authors', 'summary'));
    }

    public function create()
    {
        return view('library.authors.form', ['author' => new Author]);
    }

    public function store(StoreAuthorRequest $request)
    {
        $author = Author::create($request->validated());

        // Penambahan cepat dari form buku (AJAX): kembalikan data untuk dimasukkan ke pilihan
        if ($request->expectsJson()) {
            return response()->json(['id' => $author->id, 'name' => $author->name], 201);
        }

        return redirect()->route('authors.index')->with('success', 'Penulis berhasil ditambahkan.');
    }

    public function edit(Author $author)
    {
        return view('library.authors.form', compact('author'));
    }

    public function update(StoreAuthorRequest $request, Author $author)
    {
        $author->update($request->validated());

        return redirect()->route('authors.index')->with('success', 'Data penulis berhasil diperbarui.');
    }

    public function destroy(Author $author)
    {
        if ($author->books()->exists()) {
            throw new ResourceInUseException("Penulis {$author->name} tidak dapat dihapus karena masih tercatat pada {$author->books()->count()} buku.");
        }

        $author->delete();

        return redirect()->route('authors.index')->with('success', 'Penulis berhasil dihapus.');
    }
}
