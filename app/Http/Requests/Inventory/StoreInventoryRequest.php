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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'unit' => ['required', 'string'],
            'supplier' => ['required', 'string'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'minAlert' => ['required', 'integer'], // <-- add this

            'nutrition' => ['nullable', 'array'],
            'nutrition.calories' => ['nullable', 'numeric'],
            'nutrition.fat' => ['nullable', 'numeric'],
            'nutrition.protein' => ['nullable', 'numeric'],
            'nutrition.carbs' => ['nullable', 'numeric'],

            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['integer'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],

            'image' => ['required', 'image', 'max:2048'],
        ];
    }
}
