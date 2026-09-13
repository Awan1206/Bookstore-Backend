<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = Cart::with('book.category')->where('user_id', $request->user()->id)->get();

        return response()->json([
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'book_id' => $item->book_id,
                'title' => $item->book->title,
                'price' => (float) $item->book->sell_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'image_url' => $item->book->image ? asset('storage/' . $item->book->image) : null,
            ]),
            'total' => $items->sum(fn ($item) => $item->subtotal),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $book = Book::findOrFail($data['book_id']);

        if ($book->stock < $data['quantity']) {
            return response()->json(['message' => 'Stok buku tidak mencukupi.'], 422);
        }

        $cart = Cart::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $data['book_id']],
            []
        );
        // increment kalau item sudah ada di cart, bukan overwrite quantity
        $cart->quantity = $cart->wasRecentlyCreated ? $data['quantity'] : $cart->quantity + $data['quantity'];
        $cart->save();

        return response()->json(['message' => 'Buku ditambahkan ke keranjang.', 'data' => $cart]);
    }

    public function update(Request $request, Cart $cart)
    {
        $this->authorizeOwner($request, $cart);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($cart->book->stock < $data['quantity']) {
            return response()->json(['message' => 'Stok buku tidak mencukupi.'], 422);
        }

        $cart->update($data);

        return response()->json(['message' => 'Keranjang diperbarui.', 'data' => $cart]);
    }

    public function destroy(Request $request, Cart $cart)
    {
        $this->authorizeOwner($request, $cart);
        $cart->delete();

        return response()->json(['message' => 'Item dihapus dari keranjang.']);
    }

    private function authorizeOwner(Request $request, Cart $cart): void
    {
        abort_if($cart->user_id !== $request->user()->id, 403, 'Bukan keranjang milik Anda.');
    }
}