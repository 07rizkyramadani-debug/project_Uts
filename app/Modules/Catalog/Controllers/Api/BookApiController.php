<?php

namespace Modules\Catalog\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Book;

class BookApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $books = Book::with(['authors:id,name', 'publisher:id,name'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->boolean('available'), fn ($q) => $q->available())
            ->orderBy('title')
            ->paginate(10);

        return response()->json($books);
    }
}
