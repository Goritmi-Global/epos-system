<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class TagStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tags'        => 'required|array',
            'tags.*.name' => 'required|string|max:100|unique:tags,name',
            'tags.*.description' => 'nullable|string',
        ];
    }
    public function messages(): array
    {
        return [
            'tags.*.name.unique' => 'The ":input" tag is already exists.',
        ];
    }
}
