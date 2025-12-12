<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ✅ Check for variant_metadata instead of variant_group_id or variant_ingredients
        $isVariantMenu = is_array($this->variant_metadata) && count($this->variant_metadata) > 0;

        return [
            'name' => 'required|string|max:255|unique:menu_items,name',

            // Price is required only for simple menus
            'price' => $isVariantMenu ? 'nullable|numeric|min:0' : 'required|numeric|min:0',

            'category_id' => 'required|exists:menu_categories,id',
            'subcategory_id' => 'nullable|exists:menu_categories,id',
            'description' => 'nullable|string|max:1000',
            'label_color' => 'nullable|string|max:7',
            'is_taxable' => 'nullable|boolean',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',

            // Nutrition info
            'nutrition' => 'nullable|array',
            'nutrition.calories' => 'nullable|numeric|min:0',
            'nutrition.protein' => 'nullable|numeric|min:0',
            'nutrition.carbs' => 'nullable|numeric|min:0',
            'nutrition.fat' => 'nullable|numeric|min:0',

            // Allergies & Tags
            'allergies' => 'nullable|array',
            'allergies.*' => 'exists:allergies,id',
            'allergy_types' => 'nullable|array',
            'allergy_types.*' => 'in:0,1',

            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',

            'meals' => 'nullable|array',
            'meals.*' => 'exists:meals,id',

            'is_saleable' => 'nullable|boolean',
            'resale_type' => 'required_if:is_saleable,true|nullable|in:flat,percentage',
            'resale_value' => 'required_if:is_saleable,true|nullable|numeric|min:0',


            // 'is_saleable' => 'nullable|boolean',
            // 'resale_type' => 'required_if:is_saleable,true|nullable|in:flat,percentage',
            // 'resale_value' => 'required_if:is_saleable,true|nullable|numeric|min:0',

            // Addons
            'addon_group_id' => 'nullable|exists:addon_groups,id',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:addons,id',

            // Simple Menu Ingredients
            'ingredients' => $isVariantMenu ? 'nullable|array' : 'required|array|min:1',
            'ingredients.*.inventory_item_id' => 'required_with:ingredients|exists:inventory_items,id',
            'ingredients.*.qty' => 'required_with:ingredients|numeric|min:0.01',
            'ingredients.*.cost' => 'required_with:ingredients|numeric|min:0',

            // ✅ Variant Metadata validation
            'variant_metadata' => 'nullable|array',
            'variant_metadata.*.name' => 'required_with:variant_metadata|string|max:255',
            'variant_metadata.*.price' => 'required_with:variant_metadata|numeric|min:0',

            // is_saleable is optional but can be true (1)
            'variant_metadata.*.is_saleable' => 'nullable|boolean',

            // Conditional validation: required if is_saleable == 1
            'variant_metadata.*.resale_type' => 'required_if:variant_metadata.*.is_saleable,1|string',
            'variant_metadata.*.resale_value' => 'required_if:variant_metadata.*.is_saleable,1|numeric|min:0',

            // Variant Menu Ingredients
            'variant_ingredients' => 'nullable|array',
            'variant_ingredients.*.*.inventory_item_id' => 'required_with:variant_ingredients|exists:inventory_items,id',
            'variant_ingredients.*.*.qty' => 'required_with:variant_ingredients|numeric|min:0.01',
            'variant_ingredients.*.*.cost' => 'required_with:variant_ingredients|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Menu name is required.',
            'price.required' => 'Price is required for simple menus.',
            'category_id.required' => 'Please select a category.',
            'ingredients.required' => 'At least one ingredient is required for simple menus.',
            'ingredients.min' => 'At least one ingredient is required.',

            // Variant metadata messages
            'variant_metadata.*.name.required_with' => 'Variant name is required.',
            'variant_metadata.*.price.required_with' => 'Variant price is required.',
            'variant_metadata.*.price.min' => 'Variant price must be at least 0.',

            'variant_metadata.*.resale_type.required_if' => 'Resale type is required when the variant is saleable.',
            'variant_metadata.*.resale_type.in' => 'Resale type must be either "flat" or "percent".',
            'variant_metadata.*.resale_value.required_if' => 'Resale value is required when the variant is saleable.',
            'variant_metadata.*.resale_value.numeric' => 'Resale value must be a number.',
            'variant_metadata.*.resale_value.min' => 'Resale value must be greater than or equal to 0.',

            // Variant ingredients messages
            'variant_ingredients.*.*.inventory_item_id.required_with' => 'Ingredient is required for variant items.',
            'variant_ingredients.*.*.qty.required_with' => 'Quantity is required for variant items.',
            'variant_ingredients.*.*.qty.min' => 'Quantity must be greater than 0.',

            'resale_type.required_if' => 'Resale type is required when menu is saleable',
            'resale_type.in' => 'Resale type must be either flat or percentage',
            'resale_value.required_if' => 'Resale value is required when menu is saleable',
            'resale_value.numeric' => 'Resale value must be a number',
            'resale_value.min' => 'Resale value must be at least 0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $isVariantMenu = is_array($this->variant_metadata) && count($this->variant_metadata) > 0;

            if ($isVariantMenu) {
                // Ensure variant metadata exists
                if (empty($this->variant_metadata) || count($this->variant_metadata) === 0) {
                    $validator->errors()->add('variant_metadata', 'At least one variant is required for variant menus.');
                }

                // Ensure variant ingredients exist
                if (empty($this->variant_ingredients) || count($this->variant_ingredients) === 0) {
                    $validator->errors()->add('variant_ingredients', 'At least one variant with ingredients is required.');
                }

                // Validate that number of variants matches number of ingredient sets
                $metadataCount = count($this->variant_metadata ?? []);
                $ingredientsCount = count($this->variant_ingredients ?? []);

                if ($metadataCount !== $ingredientsCount) {
                    $validator->errors()->add('variant_ingredients', 'Each variant must have ingredients.');
                }

                // Ensure every variant has ingredients
                foreach ($this->variant_ingredients ?? [] as $variantId => $ingredients) {
                    if (empty($ingredients) || count($ingredients) === 0) {
                        $validator->errors()->add("variant_ingredients.$variantId", 'Each variant must have at least one ingredient.');
                    }
                }

                foreach ($this->variant_metadata ?? [] as $index => $variant) {
                    if (isset($variant['is_saleable']) && $variant['is_saleable'] == 1) {
                        $resaleType = $variant['resale_type'] ?? null;
                        $resaleValue = $variant['resale_value'] ?? null;

                        // Check if resale type is percentage and value exceeds 100
                        if ($resaleType === 'percentage' && $resaleValue !== null && $resaleValue > 100) {
                            $validator->errors()->add(
                                "variant_metadata.$index.resale_value",
                                'Resale value percentage cannot exceed 100%.'
                            );
                        }
                    }
                }
            }
        });
    }
}
