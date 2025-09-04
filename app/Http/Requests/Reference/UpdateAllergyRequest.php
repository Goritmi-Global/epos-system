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
        $tagId = $this->route('allergy')?->id;

        return [
            'name'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('allergies', 'name')->ignore($tagId),
            ],
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The ":input" allergy is already exists.',
        ];
    }
}
