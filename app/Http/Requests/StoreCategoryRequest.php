<?php

namespace App\Http\Requests;

use App\Models\Category;

class StoreCategoryRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Category::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
