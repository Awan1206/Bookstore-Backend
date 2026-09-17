<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CheckoutController extends Controller
{
    /**
     * User checkout dari keranjang -> jadi Order berstatus 'pending'
     * dengan Kode Pesanan unik (contoh: A021). Pembayaran cash
     * baru diproses admin di kasir (lihat Admin\OrderController::confirm).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $cartItems = Cart::with('book')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang belanja masih kosong.'], 422);
        }

        // Validasi ulang stok sebelum checkout (bisa saja berubah sejak add to cart)
        foreach ($cartItems as $item) {
            if ($item->book->stock < $item->quantity) {
                return response()->json([
                    'message' => "Stok buku \"{$item->book->title}\" tidak mencukupi.",
                ], 422);
            }
        }

        $order = DB::transaction(function () use ($user, $cartItems) {
            $orderCode = OrderCodeGenerator::generate();

            $total = $cartItems->sum(fn ($item) => $item->book->sell_price * $item->quantity);

            $order = Order::create([
                'order_code' => $orderCode,
                'user_id' => $user->id,
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $item->book_id,
                    'quantity' => $item->quantity,
                    'price' => $item->book->sell_price,
                    'subtotal' => $item->book->sell_price * $item->quantity,
                ]);

                // kurangi stok saat checkout supaya tidak oversell
                $item->book->decrement('stock', $item->quantity);
            }

            Cart::where('user_id', $user->id)->delete();

            return $order;
        });

        return response()->json([
            'message' => 'Checkout berhasil. Silakan tunjukkan QR Code ini ke kasir untuk konfirmasi pembayaran.',
            'data' => new OrderResource($order->load(['user', 'items.book'])),
            'qr_code' => $this->generateQrCode($order->order_code),
        ], 201);
    }

    // User cek status pesanannya sendiri pakai kode pesanan
    public function show(Request $request, string $orderCode)
    {
        $order = Order::with(['items.book'])
            ->where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return new OrderResource($order);
    }

    // Ambil ulang QR Code (misal user reload halaman pesanan dan butuh tampilkan QR lagi)
    public function qrCode(Request $request, string $orderCode)
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'order_code' => $order->order_code,
                'qr_code' => $this->generateQrCode($order->order_code),
            ],
        ]);
    }

    // Generate QR Code (base64 PNG) berisi Kode Pesanan, dipakai kasir untuk scan konfirmasi
    private function generateQrCode(string $orderCode): string
    {
        $svg = QrCode::format('svg')->size(200)->generate($orderCode);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}