<?php

namespace App\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'upload_id' => 'nullable|exists:uploads,id',
            'status' => 'sometimes|boolean',
            'menu_item_ids' => 'sometimes|required|array|min:1',
            'menu_item_ids.*' => 'exists:menu_items,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Deal name is required',
            'name.max' => 'Deal name cannot exceed 255 characters',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price cannot be negative',
            'description.max' => 'Description cannot exceed 1000 characters',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format',
            'image.max' => 'Image size cannot exceed 2MB',
            'upload_id.exists' => 'Invalid upload reference',
            'menu_item_ids.required' => 'Please select at least one menu item',
            'menu_item_ids.min' => 'Please select at least one menu item',
            'menu_item_ids.array' => 'Menu items must be an array',
            'menu_item_ids.*.exists' => 'One or more selected menu items are invalid',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert status to boolean if present
        if ($this->has('status')) {
            $this->merge([
                'status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $this->deal->status ?? true,
            ]);
        }
    }
}