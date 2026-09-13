<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
            ],
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'cash_received' => $this->cash_received !== null ? (float) $this->cash_received : null,
            'change_amount' => $this->change_amount !== null ? (float) $this->change_amount : null,
            'confirmed_by' => $this->whenLoaded('confirmedBy', fn () => $this->confirmedBy?->name),
            'confirmed_at' => $this->confirmed_at,
            'items' => $this->items->map(fn ($item) => [
                'book_id' => $item->book_id,
                'title' => $item->book->title,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}