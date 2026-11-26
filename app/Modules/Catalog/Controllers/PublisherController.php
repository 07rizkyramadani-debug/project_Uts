<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Publisher;
use Modules\Catalog\Requests\StorePublisherRequest;
use Modules\Shared\Exceptions\ResourceInUseException;

class PublisherController extends Controller
{
    public function index(Request $request)
    {
        $publishers = Publisher::withCount('books')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q')->trim().'%')
                ->orWhere('city', 'like', '%'.$request->string('q')->trim().'%')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'all' => Publisher::count(),
            'with_books' => Publisher::has('books')->count(),
            'cities' => Publisher::whereNotNull('city')->where('city', '!=', '')->distinct()->count('city'),
        ];

        return view('library.publishers.index', compact('publishers', 'summary'));
    }

    public function create()
    {
        return view('library.publishers.form', ['publisher' => new Publisher]);
    }

    public function store(StorePublisherRequest $request)
    {
        $publisher = Publisher::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['id' => $publisher->id, 'name' => $publisher->name], 201);
        }

        return redirect()->route('publishers.index')->with('success', 'Penerbit berhasil ditambahkan.');
    }

    public function edit(Publisher $publisher)
    {
        return view('library.publishers.form', compact('publisher'));
    }

    public function update(StorePublisherRequest $request, Publisher $publisher)
    {
        $publisher->update($request->validated());

        return redirect()->route('publishers.index')->with('success', 'Data penerbit berhasil diperbarui.');
    }

    public function destroy(Publisher $publisher)
    {
        if ($publisher->books()->exists()) {
            throw new ResourceInUseException("Penerbit {$publisher->name} tidak dapat dihapus karena masih dipakai oleh {$publisher->books()->count()} buku.");
        }

        $publisher->delete();

        return redirect()->route('publishers.index')->with('success', 'Penerbit berhasil dihapus.');
    }
}
