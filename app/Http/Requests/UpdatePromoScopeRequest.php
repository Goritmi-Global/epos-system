<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'promos' => 'required|array',
            'promos.*' => 'exists:promos,id',   
            'meals' => 'nullable|array',
            'meals.*' => 'exists:meals,id',   
            'menu_items' => 'nullable|array',
            'menu_items.*' => 'exists:menu_items,id',
        ];
    }

    public function messages(): array
    {
        return [
            'promos.required' => 'Please select at least one promo',
            'promos.array' => 'Promos must be an array',
            'promos.*.exists' => 'Selected promo does not exist',
            'meals.array' => 'Meals must be an array',
            'meals.*.exists' => 'Selected meal does not exist',
            'menu_items.array' => 'Menu items must be an array',
            'menu_items.*.exists' => 'Selected menu item does not exist',
        ];
    }
}
