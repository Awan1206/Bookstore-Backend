<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_code', 'user_id', 'total_amount', 'status',
        'cash_received', 'change_amount', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Pakai order_code (bukan id) untuk route model binding,
     * karena kasir/admin hanya punya order_code hasil scan QR
     * (contoh: /admin/orders/ORD-000001/confirm).
     */
    public function getRouteKeyName(): string
    {
        return 'order_code';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}