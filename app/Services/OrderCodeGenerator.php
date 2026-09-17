<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderCodeGenerator
{
    /**
     * Generate kode pesanan unik yang terus bertambah, format: ORD-000001, ORD-000002, dst.
     *
     * Dipanggil di dalam DB::transaction() milik CheckoutController, jadi kita
     * kunci baris terakhir (lockForUpdate) supaya aman dari race condition
     * kalau ada 2 checkout masuk bersamaan.
     */
    public static function generate(): string
    {
        $prefix = 'ORD-';
        $padLength = 6;

        $lastNumber = DB::transaction(function () use ($prefix) {
            $lastOrder = Order::where('order_code', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $lastOrder) {
                return 0;
            }

            // Ambil bagian angka setelah prefix, contoh "ORD-000042" -> 42
            $numericPart = (int) str_replace($prefix, '', $lastOrder->order_code);

            return $numericPart;
        });

        $nextNumber = $lastNumber + 1;

        return $prefix.str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
    }
}