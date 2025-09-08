<?php
namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isSubCategory' => 'required|boolean',
            
            // Categories array validation
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.icon' => 'sometimes|string|max:10',
            'categories.*.active' => 'sometimes|boolean',
            'categories.*.parent_id' => 'nullable|integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'categories.*.parent_id.exists' => 'The selected parent category does not exist.',
            'categories.required' => 'At least one category must be provided.',
            'categories.*.name.required' => 'Each category must have a name.',
        ];
    }

    public function attributes(): array
    {
        return [
            'isSubCategory' => 'subcategory flag',
            'categories.*.name' => 'category name',
            'categories.*.icon' => 'category icon',
            'categories.*.parent_id' => 'parent category',
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
            $isSubCategory = $this->input('isSubCategory');
            $categories = $this->input('categories', []);
            
            // ✅ CHECK FOR DUPLICATES WITHIN THE REQUEST
            $categoryNames = [];
            
            foreach ($categories as $index => $category) {
                $parentId = $category['parent_id'] ?? null;
                $categoryName = trim($category['name'] ?? '');
                
                // Skip empty names (will be caught by required validation)
                if (empty($categoryName)) {
                    continue;
                }
                
                // ✅ CHECK FOR DUPLICATES IN THE CURRENT REQUEST
                $duplicateKey = $parentId . '|' . strtolower($categoryName);
                if (in_array($duplicateKey, $categoryNames)) {
                    $validator->errors()->add(
                        "categories.{$index}.name",
                        "Duplicate category name '{$categoryName}' found in the request."
                    );
                    continue;
                }
                $categoryNames[] = $duplicateKey;
                
                // ✅ CHECK FOR DUPLICATES IN DATABASE
                $existingCategory = \App\Models\Category::where('name', $categoryName)
                    ->where('parent_id', $parentId)
                    ->first();
                    
                if ($existingCategory) {
                    $categoryType = $isSubCategory ? 'subcategory' : 'category';
                    $parentInfo = $parentId ? ' under this parent category' : '';
                    
                    $validator->errors()->add(
                        "categories.{$index}.name",
                        "The {$categoryType} {$categoryName} already exists{$parentInfo}."
                    );
                    continue;
                }
                
                if ($isSubCategory) {
                    // ✅ CREATING SUBCATEGORY - parent_id MUST exist
                    if (!$parentId) {
                        $validator->errors()->add(
                            "categories.{$index}.parent_id", 
                            'Parent category is required when creating a subcategory.'
                        );
                        continue;
                    }
                    
                    // Ensure parent is a top-level category (not a subcategory itself)
                    $parentExists = \App\Models\Category::where('id', $parentId)
                        ->whereNull('parent_id') // must be a main category
                        ->exists();

                    if (!$parentExists) {
                        $validator->errors()->add(
                            "categories.{$index}.parent_id", 
                            'Selected parent category must be a main category, not a subcategory.'
                        );
                    }
                } else {
                    // ✅ CREATING PARENT CATEGORY - parent_id MUST be null
                    if ($parentId !== null) {
                        $validator->errors()->add(
                            "categories.{$index}.parent_id", 
                            'Parent category cannot have a parent_id when creating main categories.'
                        );
                    }
                }
            }
        });
    }
}