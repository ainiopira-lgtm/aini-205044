<?php

namespace App\Http\Requests;

use App\Models\Shelf;
use Illuminate\Validation\Rule;

class UpdateShelfRequest extends CatalogFormRequest
{
    protected function modelClass(): string
    {
        return Shelf::class;
    }

    public function rules(): array
    {
        $shelf = $this->route('shelf');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('shelves', 'code')->ignore($shelf->id)],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
