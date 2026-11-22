<?php

namespace App\Http\Requests;

use App\Models\Author;

class UpdateAuthorRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Author::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
