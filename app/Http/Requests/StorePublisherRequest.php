<?php

namespace App\Http\Requests;

use App\Models\Publisher;

class StorePublisherRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Publisher::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:publishers,name'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
