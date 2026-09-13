<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('books', 'title')->ignore($bookId),
            ],
            'publish_date' => ['nullable', 'date'],
            'publish_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'stock' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0', 'gte:cost_price'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Buku dengan judul ini sudah ada.',
            'sell_price.gte' => 'Harga jual tidak boleh lebih kecil dari harga modal.',
        ];
    }
}