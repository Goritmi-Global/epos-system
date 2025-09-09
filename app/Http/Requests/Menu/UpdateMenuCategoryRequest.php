<?php
namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:10',
            'active' => 'boolean',
            'parent_id' => 'nullable|exists:categories,id',
            'subcategories' => 'nullable|array',
            'subcategories.*.id' => 'nullable|exists:categories,id',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'subcategories.*.name.required' => 'Each subcategory must have a name.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->route('id'); // Get the category ID from route
            $categoryName = trim($this->input('name', ''));
            $parentId = $this->input('parent_id');
            $subcategories = $this->input('subcategories', []);

            // ✅ CHECK MAIN CATEGORY NAME FOR DUPLICATES
            if (!empty($categoryName)) {
                $duplicateCategory = \App\Models\InventoryCategory::where('name', $categoryName)
                    ->where('parent_id', $parentId)
                    ->where('id', '!=', $categoryId) // Exclude current category
                    ->first();

                if ($duplicateCategory) {
                    $parentInfo = $parentId ? ' under this parent category' : '';
                    $validator->errors()->add(
                        'name',
                        "The category '{$categoryName}' already exists{$parentInfo}."
                    );
                }
            }

            // ✅ CHECK SUBCATEGORIES FOR DUPLICATES
            if (!empty($subcategories)) {
                $subcategoryNames = [];
                
                foreach ($subcategories as $index => $subcategory) {
                    $subcategoryName = trim($subcategory['name'] ?? '');
                    $subcategoryId = $subcategory['id'] ?? null;
                    
                    if (empty($subcategoryName)) {
                        continue;
                    }

                    // Check for duplicates within the request
                    $subcategoryKey = strtolower($subcategoryName);
                    if (in_array($subcategoryKey, $subcategoryNames)) {
                        $validator->errors()->add(
                            "subcategories.{$index}.name",
                            "Duplicate subcategory name '{$subcategoryName}' found in the request."
                        );
                        continue;
                    }
                    $subcategoryNames[] = $subcategoryKey;

                    // Check for duplicates in database
                    $query = \App\Models\InventoryCategory::where('name', $subcategoryName)
                        ->where('parent_id', $categoryId);
                        
                    // Exclude current subcategory if it has an ID
                    if ($subcategoryId) {
                        $query->where('id', '!=', $subcategoryId);
                    }
                    
                    if ($query->exists()) {
                        $validator->errors()->add(
                            "subcategories.{$index}.name",
                            "The subcategory '{$subcategoryName}' already exists under this category."
                        );
                    }
                }
            }

            // ✅ VALIDATE PARENT CATEGORY LOGIC
            if ($parentId) {
                // Ensure parent is a top-level category
                $parentExists = \App\Models\InventoryCategory::where('id', $parentId)
                    ->whereNull('parent_id')
                    ->exists();

                if (!$parentExists) {
                    $validator->errors()->add(
                        'parent_id',
                        'Selected parent category must be a main category, not a subcategory.'
                    );
                }

                // Prevent circular reference
                if ($parentId == $categoryId) {
                    $validator->errors()->add(
                        'parent_id',
                        'Category cannot be its own parent.'
                    );
                }
            }
        });
    }
}