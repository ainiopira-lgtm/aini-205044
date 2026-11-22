<?php

namespace App\Http\Requests;

use App\Models\Shelf;

class StoreShelfRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Shelf::class;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:shelves,code'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
