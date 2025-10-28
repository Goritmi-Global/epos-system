<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get the current variant group ID from the route parameter
        $groupId = $this->route('variant_group') ?? $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Ignore the current record when checking uniqueness
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
            'status.required' => 'Please select a status.',
        ];
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_select' => (int) ($this->min_select ?? 0),
            'max_select' => (int) ($this->max_select ?? 0),
        ]);
    }
}
