<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::withCount('books')->orderBy('name')->get());
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category,
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category,
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak bisa dihapus karena masih dipakai oleh buku.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}