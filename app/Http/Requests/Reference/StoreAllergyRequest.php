<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allergies'        => 'required|array',
            'allergies.*.name' => 'required|string|max:100|unique:allergies,name',
            'allergies.*.description' => 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'allergies.*.name.unique' => 'The ":input" is allergy already exists.',
        ];
    }
}
