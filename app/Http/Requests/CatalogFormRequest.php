<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class CatalogFormRequest extends FormRequest
{
    abstract protected function modelClass(): string;

    public function authorize(): bool
    {
        $ability = $this->isMethod('POST') ? 'create' : 'update';
        $model = $ability === 'create'
            ? $this->modelClass()
            : $this->route(Str::camel(class_basename($this->modelClass())));

        return $this->user()?->can($ability, $model) ?? false;
    }
}
