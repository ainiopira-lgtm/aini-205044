<?php

namespace App\Http\Requests;

use App\Models\Publisher;
use Illuminate\Validation\Rule;

class UpdatePublisherRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Publisher::class;
    }

    public function rules(): array
    {
        $publisher = $this->route('publisher');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('publishers', 'name')->ignore($publisher->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
