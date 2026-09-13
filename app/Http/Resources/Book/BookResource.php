<?php

namespace App\Http\Resources\Book;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'publish_date' => $this->publish_date,
            'publish_year' => $this->publish_year,
            'stock' => $this->stock,
            'cost_price' => (float) $this->cost_price,
            'sell_price' => (float) $this->sell_price,
            'profit' => (float) $this->profit,
            'description' => $this->description,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'created_at' => $this->created_at,
        ];
    }
}