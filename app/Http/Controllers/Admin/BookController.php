<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\Book\BookResource;
use App\Models\Book;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with(['category:id,name'])
            ->select([
                'id', 'category_id', 'title',
                'publish_year', 'stock',
                'cost_price', 'sell_price',
                'image', 'created_at',
            ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => BookResource::collection($books),
            'meta' => [
                'current_page' => $books->currentPage(),
                'per_page'     => $books->perPage(),
                'total'        => $books->total(),
                'last_page'    => $books->lastPage(),
            ],
        ]);
    }

    public function store(StoreBookRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('books', 'public');
        }

        $book = Book::create($data);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan.',
            'data' => new BookResource($book->load('category')),
        ], 201);
    }

    public function show(Book $book)
    {
        return new BookResource($book->load('category'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // hapus gambar lama supaya storage tidak menumpuk file tak terpakai
            if ($book->image) {
                Storage::disk('public')->delete($book->image);
            }
            $data['image'] = $request->file('image')->store('books', 'public');
        }

        $book->update($data);

        return response()->json([
            'message' => 'Buku berhasil diperbarui.',
            'data' => new BookResource($book->load('category')),
        ]);
    }

    public function destroy(Book $book)
    {
        if ($book->image) {
            Storage::disk('public')->delete($book->image);
        }

        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus.']);
    }
}