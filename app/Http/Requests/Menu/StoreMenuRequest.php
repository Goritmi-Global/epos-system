<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // allow all authenticated users (you can add policies later if needed)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:menu_items,slug'],
            'category_id' => ['required', 'numeric', 'exists:menu_categories,id'],
            'subcategory_id' => ['nullable', 'numeric', 'exists:menu_categories,id'],
            'description' => ['nullable', 'string'],
            'label_color' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],

            'is_taxable' => ['nullable', 'boolean'],

            // nutrition
            'nutrition' => ['array'],
            'nutrition.calories' => ['nullable', 'numeric', 'min:0'],
            'nutrition.protein' => ['nullable', 'numeric', 'min:0'],
            'nutrition.fat' => ['nullable', 'numeric', 'min:0'],
            'nutrition.carbs' => ['nullable', 'numeric', 'min:0'],

            // allergies + tags (now nullable)
            // 'allergies' => ['nullable', 'array'],
            // 'allergies.*' => ['numeric', 'exists:allergies,id'],
            // 'allergies.*.id' => ['required', 'numeric', 'exists:allergies,id'],
            // 'allergies.*.type' => ['required', 'boolean'],
            // 'tags' => ['nullable', 'array'],
            // 'tags.*' => ['numeric', 'exists:tags,id'],
            // allergies + tags (now nullable)
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
            'image' => ['required', 'image', 'max:2048'],
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
