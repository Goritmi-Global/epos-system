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
            'name'            => ['required','string','max:255'],
            'category_id'     => ['required','integer','exists:inventory_categories,id'],
            'subcategory_id'  => ['nullable','integer','exists:inventory_categories,id'],
            'unit_id'         => ['required','integer','exists:units,id'],
            'supplier_id'     => ['required','integer','exists:suppliers,id'],

            // if SKU must be unique on create:
            'sku'             => ['nullable','string','max:255','unique:inventory_items,sku'],

            'description'     => ['nullable','string'],
            'minAlert'        => ['required','integer','min:0'],

            'nutrition'               => ['nullable','array'],
            'nutrition.calories'      => ['nullable','numeric','min:0'],
            'nutrition.fat'           => ['nullable','numeric','min:0'],
            'nutrition.protein'       => ['nullable','numeric','min:0'],
            'nutrition.carbs'         => ['nullable','numeric','min:0'],

            'allergies'       => ['nullable','array'],
            'allergies.*'     => ['integer','distinct','exists:allergies,id'],
            'tags'            => ['nullable','array'],
            'tags.*'          => ['integer','distinct','exists:tags,id'],

            'image'           => ['required','image','max:2048'], // KB
        ];
    }

}
