<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'units'        => 'required|array',
            'units.*.name' => 'required|string|max:100|unique:units,name',
            'units.*.description' => 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'units.*.name.unique' => 'The ":input" is allergy already exists.',
        ];
    }
}
