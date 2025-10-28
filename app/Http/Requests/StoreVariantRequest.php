<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
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
        $variantId = $this->route('variant'); 
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variants', 'name')
                    ->where('variant_group_id', $this->variant_group_id)
                    ->ignore($variantId)
            ],
            'variant_group_id' => 'required|exists:variant_groups,id',
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
            'name.required' => 'Variant name is required.',
            'name.unique' => 'This variant already exists in the selected group.',
            'variant_group_id.required' => 'Please select a variant group.',
            'variant_group_id.exists' => 'Selected variant group does not exist.',
        ];
    }

}