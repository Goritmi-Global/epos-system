<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // set to false if you want to restrict access
    }

    public function rules(): array
    {
        return [
            'promos' => 'required|array',
            'promos.*' => 'exists:promos,id',
            'meals' => 'required|array',
            'meals.*' => 'exists:meals,id',
            'menu_items' => 'required|array',
            'menu_items.*' => 'exists:menu_items,id',
        ];
    }


    public function messages(): array
    {
        return [
            'promo_id.required' => 'Promo is required',
            'promo_id.exists' => 'Selected promo does not exist',
            'meals.array' => 'Meals must be an array',
            'meals.*.exists' => 'Selected meal does not exist',
            'menu_items.array' => 'Menu items must be an array',
            'menu_items.*.exists' => 'Selected menu item does not exist',
        ];
    }
}
