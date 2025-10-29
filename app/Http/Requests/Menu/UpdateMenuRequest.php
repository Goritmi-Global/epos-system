<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuId = $this->route('menu');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('menu_items', 'slug')->ignore($menuId),
            ],
            'category_id' => ['required', 'numeric', 'exists:menu_categories,id'],
            'meals' => ['nullable', 'array'], // Add this
            'meals.*' => ['numeric', 'exists:meals,id'], // Add this
            'subcategory_id' => ['nullable', 'numeric', 'exists:menu_categories,id'],
            'description' => ['nullable', 'string'],
            'label_color' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],

            // nutrition
            'nutrition' => ['array'],
            'nutrition.calories' => ['nullable', 'numeric', 'min:0'],
            'nutrition.protein' => ['nullable', 'numeric', 'min:0'],
            'nutrition.fat' => ['nullable', 'numeric', 'min:0'],
            'nutrition.carbs' => ['nullable', 'numeric', 'min:0'],

            // allergies + tags (now optional)
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['numeric', 'exists:allergies,id'],
            'allergy_types' => ['nullable', 'array'],
            'allergy_types.*' => ['boolean'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['numeric', 'exists:tags,id'],

            // ingredients
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.inventory_item_id' => ['required', 'numeric', 'exists:inventory_items,id'],
            'ingredients.*.qty' => ['required', 'numeric', 'min:0'],
            'ingredients.*.unit_price' => ['required', 'numeric', 'min:0'],
            'ingredients.*.cost' => ['required', 'numeric', 'min:0'],

            // image
            'image' => ['nullable', 'image', 'max:2048'],
            'variant_group_id' => ['nullable', 'numeric', 'exists:variant_groups,id'],
            'variant_prices' => ['nullable', 'array'],
            'variant_prices.*' => ['nullable', 'numeric', 'min:0'],
            'addon_group_id' => ['nullable', 'numeric', 'exists:addon_groups,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'subcategory_id' => 'subcategory',
            'ingredients.*.inventory_item_id' => 'ingredient item',
        ];
    }
}
