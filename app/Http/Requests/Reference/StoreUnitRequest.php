<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // allow all for now
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:units,name',
        ];
    }
}
