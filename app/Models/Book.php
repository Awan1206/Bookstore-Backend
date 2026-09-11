<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'category_id', 'title', 'publish_date', 'publish_year',
        'stock', 'cost_price', 'sell_price', 'description', 'image',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'cost_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Keuntungan dihitung on-the-fly, tidak disimpan sebagai kolom terpisah
    public function getProfitAttribute()
    {
        return $this->sell_price - $this->cost_price;
    }
}