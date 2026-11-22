<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Book::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255', 'unique:books,isbn'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'year' => ['required', 'integer', 'between:1000,'.((int) date('Y') + 1)],
            'synopsis' => ['required', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['required', 'integer', 'distinct', 'exists:authors,id'],
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
