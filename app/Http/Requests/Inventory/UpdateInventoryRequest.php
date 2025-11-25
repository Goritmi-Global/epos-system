<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('inventory') instanceof \App\Models\InventoryItem
            ? $this->route('inventory')->id
            : $this->route('inventory'); // works for id or model binding

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_items', 'name')->ignore($id),
            ],

            'category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],

            'sku' => [
                'nullable', 'string', 'max:255',
                Rule::unique('inventory_items', 'sku')->ignore($id),
            ],

            'description' => ['nullable', 'string'],
            'minAlert' => ['required', 'integer', 'min:0'],

            'nutrition' => ['required', 'array'],
            'nutrition.calories' => ['required', 'numeric'],
            'nutrition.fat' => ['required', 'numeric'],
            'nutrition.protein' => ['required', 'numeric'],
            'nutrition.carbs' => ['required', 'numeric'],

            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['integer', 'distinct', 'exists:allergies,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'distinct', 'exists:tags,id'],

            'image' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nutrition.required' => 'The nutrition information is required.',
            'nutrition.calories.required' => 'The calories field is required.',
            'nutrition.fat.required' => 'The fat field is required.',
            'nutrition.protein.required' => 'The protein field is required.',
            'nutrition.carbs.required' => 'The carbs field is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nutrition.calories' => 'calories',
            'nutrition.fat' => 'fat',
            'nutrition.protein' => 'protein',
            'nutrition.carbs' => 'carbs',
        ];
    }
}
