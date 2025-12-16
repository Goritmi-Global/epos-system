<?php

namespace App\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic deal info
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'upload_id' => 'nullable|exists:uploads,id',
            'status' => 'sometimes|boolean',
            'is_taxable' => 'sometimes|boolean',
            'label_color' => 'nullable|string|max:7',
            'category_id' => 'nullable|exists:menu_categories,id',

            // ✅ FIXED: Single addon group (not array)
            'addon_group_ids' => 'nullable|array',
            'addon_group_ids.*' => 'exists:addon_groups,id',

            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:addons,id',

            // ✅ FIXED: Allergies as array of IDs (not objects)
            'allergies' => 'nullable|array',
            'allergies.*' => 'exists:allergies,id',
            'allergy_types' => 'nullable|array',
            'allergy_types.*' => 'in:0,1',

            // Tags
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',

            // Meals
            'meals' => 'nullable|array',
            'meals.*' => 'exists:meals,id',

            // Menu items with quantities
            'menu_item_ids' => 'sometimes|required|array|min:1',
            'menu_item_ids.*.id' => 'required|exists:menu_items,id',
            'menu_item_ids.*.qty' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Deal name is required',
            'name.max' => 'Deal name cannot exceed 255 characters',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price cannot be negative',
            'description.max' => 'Description cannot exceed 1000 characters',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format',
            'image.max' => 'Image size cannot exceed 2MB',
            'upload_id.exists' => 'Invalid upload reference',
            'menu_item_ids.required' => 'Please select at least one menu item',
            'menu_item_ids.min' => 'Please select at least one menu item',
            'menu_item_ids.array' => 'Menu items must be an array',
            'menu_item_ids.*.id.exists' => 'One or more selected menu items are invalid',
            'menu_item_ids.*.qty.required' => 'Quantity is required for each menu item',
            'menu_item_ids.*.qty.integer' => 'Quantity must be a number',
            'menu_item_ids.*.qty.min' => 'Quantity must be at least 1',

            'category_id.exists' => 'Selected category is invalid',
            'addon_group_ids.*.exists' => 'One or more selected addon groups are invalid',
            'addon_ids.*.exists' => 'One or more selected addons are invalid',

            'allergies.*.exists' => 'One or more selected allergies are invalid',
            'allergy_types.*.in' => 'Allergy type must be 0 or 1',
            'tags.*.exists' => 'One or more selected tags are invalid',
            'meals.*.exists' => 'One or more selected meals are invalid',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert boolean fields
        $booleanFields = ['status', 'is_taxable'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                ]);
            }
        }
    }
}
