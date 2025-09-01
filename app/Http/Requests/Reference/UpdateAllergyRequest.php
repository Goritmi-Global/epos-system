<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Expect "name" to be an array
            'name' => 'required|string',
            'name.*' => [
                'required',
                'string',
                'max:100',
                Rule::unique('allergies', 'name')->ignore($this->allergy->id),
            ],

            'description' => 'nullable|string|max:255',
        ];
    }
}

