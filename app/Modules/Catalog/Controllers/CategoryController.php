<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Requests\StoreCategoryRequest;
use Modules\Shared\Exceptions\ResourceInUseException;

class CategoryController extends Controller
{
    public function index()
    {
        // Eager loading: sub-kategori + jumlah buku dihitung dalam query yang sama
        $categories = Category::roots()
            ->with(['children' => fn ($q) => $q->withCount('books')])
            ->withCount('books')
            ->orderBy('name')
            ->get();

        $summary = [
            'all' => Category::count(),
            'roots' => $categories->count(),
            'subs' => $categories->sum(fn ($c) => $c->children->count()),
            'books' => DB::table('book_category')->distinct()->count('book_id'),
        ];

        return view('library.categories.index', compact('categories', 'summary'));
    }

    public function create()
    {
        return view('library.categories.form', [
            'category' => new Category,
            'parents' => Category::roots()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->safe()->only(['name', 'slug', 'description', 'parent_id']));

        if ($request->expectsJson()) {
            return response()->json(['id' => $category->id, 'name' => $category->name, 'parent_id' => $category->parent_id], 201);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('library.categories.form', [
            'category' => $category,
            // Kategori yang punya sub-kategori tidak boleh dijadikan sub-kategori lagi (maksimal dua tingkat)
            'parents' => Category::roots()->whereKeyNot($category->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $data = $request->safe()->only(['name', 'slug', 'description', 'parent_id']);

        if ($category->children()->exists() && ! empty($data['parent_id'])) {
            throw new ResourceInUseException("Kategori {$category->name} masih memiliki sub-kategori sehingga tidak dapat dijadikan sub-kategori.");
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            throw new ResourceInUseException("Kategori {$category->name} tidak dapat dihapus karena masih memiliki sub-kategori.");
        }

        if ($category->books()->exists()) {
            throw new ResourceInUseException("Kategori {$category->name} tidak dapat dihapus karena masih dipakai oleh {$category->books()->count()} buku.");
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
