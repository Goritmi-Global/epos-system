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
        $id = $this->route('inventory');
        return [
            // Fix: Change 'inventories' to 'inventory_items'
            'sku' => ['required','string','max:50', Rule::unique('inventory_items','sku')->ignore($id)],
            'name' => ['required','string','max:255'],
            'category' => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'unit' => ['required', 'string'],
            'supplier' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'minAlert' => ['nullable', 'integer'],
            'nutrition' => ['nullable', 'array'],
            'nutrition.calories' => ['nullable', 'numeric'],
            'nutrition.fat' => ['nullable', 'numeric'],
            'nutrition.protein' => ['nullable', 'numeric'],
            'nutrition.carbs' => ['nullable', 'numeric'],

            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['integer'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],

            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}