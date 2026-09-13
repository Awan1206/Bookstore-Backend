<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\ConfirmOrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Monitor semua pesanan masuk, bisa difilter status: ?status=pending
    public function index(Request $request)
    {
        $orders = Order::with(['user', 'items.book'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->order_code, fn ($q) => $q->where('order_code', 'like', "%{$request->order_code}%"))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['user', 'items.book', 'confirmedBy']));
    }

    /**
     * Admin konfirmasi pesanan berdasarkan Kode Pesanan (contoh: A021),
     * input nominal cash, sistem hitung kembalian otomatis.
     */
    public function confirm(ConfirmOrderRequest $request, Order $order)
    {
        if (in_array($order->status, ['paid', 'completed', 'cancelled'])) {
            return response()->json(['message' => 'Pesanan ini sudah tidak bisa dikonfirmasi ulang.'], 422);
        }

        if ($request->cash_received < $order->total_amount) {
            return response()->json([
                'message' => 'Uang cash yang diterima kurang dari total belanja.',
            ], 422);
        }

        $change = $request->cash_received - $order->total_amount;

        $order->update([
            'status' => 'paid',
            'cash_received' => $request->cash_received,
            'change_amount' => $change,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pesanan berhasil dikonfirmasi dan dibayar.',
            'data' => new OrderResource($order->load(['user', 'items.book', 'confirmedBy'])),
        ]);
    }

    // Menandai pesanan selesai (opsional, dipisah dari 'paid' kalau perlu alur tambahan)
    public function complete(Order $order)
    {
        if ($order->status !== 'paid') {
            return response()->json(['message' => 'Pesanan harus berstatus "paid" sebelum diselesaikan.'], 422);
        }

        $order->update(['status' => 'completed']);

        return response()->json(['message' => 'Pesanan ditandai selesai.']);
    }
}