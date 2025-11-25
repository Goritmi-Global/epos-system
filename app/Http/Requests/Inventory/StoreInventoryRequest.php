<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           'name'            => ['required', 'string', 'max:255', 'unique:inventory_items,name'],
            'category_id'     => ['required', 'integer', 'exists:inventory_categories,id'],
            'subcategory_id'  => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'unit_id'         => ['required', 'integer', 'exists:units,id'],
            'supplier_id'     => ['required', 'integer', 'exists:suppliers,id'],
            'sku'             => ['nullable', 'string', 'max:255', 'unique:inventory_items,sku'],
            'description'     => ['nullable', 'string'],
            'minAlert'        => ['required', 'integer', 'min:0'],

            'nutrition'               => ['required', 'array'],
            'nutrition.calories'      => ['required', 'numeric', 'min:0'],
            'nutrition.fat'           => ['required', 'numeric', 'min:0'],
            'nutrition.protein'       => ['required', 'numeric', 'min:0'],
            'nutrition.carbs'         => ['required', 'numeric', 'min:0'],

            'allergies'       => ['nullable', 'array'],
            'allergies.*'     => ['integer', 'distinct', 'exists:allergies,id'],
            'tags'            => ['nullable', 'array'],
            'tags.*'          => ['integer', 'distinct', 'exists:tags,id'],

            'image'           => ['required', 'image', 'max:2048'], // KB
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

    /**
     * Rename attributes so dot notation doesn't appear in errors
     */
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
