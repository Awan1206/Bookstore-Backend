<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Book\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    // GET /api/catalog?q=harry&category_id=2
    public function index(Request $request)
    {
        $books = Book::with('category')
            ->when($request->q, fn ($q) => $q->where('title', 'like', "%{$request->q}%"))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->orderBy('title')
            ->paginate(12);

        return response()->json([
            'data' => BookResource::collection($books->items()),
            'meta' => [
                'current_page' => $books->currentPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
                'last_page' => $books->lastPage(),
            ],
        ]);
    }

    public function show(Book $book)
    {
        return new BookResource($book->load('category'));
    }
}