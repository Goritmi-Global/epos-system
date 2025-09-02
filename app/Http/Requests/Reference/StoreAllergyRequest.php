<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllergyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'names'   => 'required|array|min:1',
            'names.*' => 'required|string|max:100|distinct|unique:allergies,name',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'names.required' => 'At least one allergy name is required.',
            'names.array' => 'Allergy names must be provided as an array.',
            'names.min' => 'At least one allergy must be provided.',
            'names.*.required' => 'Allergy name cannot be empty.',
            'names.*.string' => 'Allergy name must be a valid string.',
            'names.*.max' => 'Allergy name cannot exceed 100 characters.',
            'names.*.distinct' => 'Duplicate allergy names are not allowed in the same request.',
            'names.*.unique' => 'The allergy ":input" already exists.',
        ];
    }

    /**
     * Custom attribute names for better error messages
     */
    public function attributes(): array
    {
        return [
            'names.*' => 'allergy name',
        ];
    }
}