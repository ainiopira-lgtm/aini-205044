<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('book')) ?? false;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255', Rule::unique('books', 'isbn')->ignore($book->id)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'year' => ['required', 'integer', 'between:1000,'.((int) date('Y') + 1)],
            'synopsis' => ['required', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['required', 'integer', 'distinct', 'exists:authors,id'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
