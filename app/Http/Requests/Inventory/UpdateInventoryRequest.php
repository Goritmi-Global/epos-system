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
        $id = $this->route('inventory') instanceof \App\Models\Inventory
            ? $this->route('inventory')->id
            : $this->route('inventory'); // works for id or model binding

        return [
            'name'            => ['required','string','max:255'],
            'category_id'     => ['nullable','integer','exists:inventory_categories,id'],
            'subcategory_id'  => ['nullable','integer','exists:inventory_categories,id'],
            'unit_id'         => ['required','integer','exists:units,id'],
            'supplier_id'     => ['nullable','integer','exists:suppliers,id'],

            'sku'             => [
                'nullable','string','max:255',
                Rule::unique('inventory_items','sku')->ignore($id),
            ],

            'description'     => ['nullable','string'],
            'minAlert'        => ['nullable','integer','min:0'],

            'nutrition'               => ['nullable','array'],
            'nutrition.calories'      => ['nullable','numeric','min:0'],
            'nutrition.fat'           => ['nullable','numeric','min:0'],
            'nutrition.protein'       => ['nullable','numeric','min:0'],
            'nutrition.carbs'         => ['nullable','numeric','min:0'],

            'allergies'       => ['nullable','array'],
            'allergies.*'     => ['integer','distinct','exists:allergies,id'],
            'tags'            => ['nullable','array'],
            'tags.*'          => ['integer','distinct','exists:tags,id'],

            // Optional on update:
            'image'           => ['sometimes','nullable','image','max:2048'],
        ];
    }
}
