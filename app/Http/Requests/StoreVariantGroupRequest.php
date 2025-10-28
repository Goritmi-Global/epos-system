<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $groupId = $this->variant_group?->id ?? $this->route('variant_group');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variant_groups', 'name')->ignore($groupId),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Variant group name is required.',
            'name.unique' => 'This variant group name already exists.',
        ];
    }

    /**
     * Prepare data for validation
     * Ensures min/max values are integers
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_select' => (int) $this->min_select,
            'max_select' => (int) $this->max_select,
        ]);
    }
}
