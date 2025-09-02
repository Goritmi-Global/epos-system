<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categoryId = $this->route('id'); // Assuming the route parameter is 'id'
        
        return [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $categoryId,
            'icon' => 'sometimes|string|max:10',
            'active' => 'sometimes|boolean',
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$categoryId]) // Prevent self-reference
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A category with this name already exists.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'category name',
            'icon' => 'category icon',
            'active' => 'active status',
            'parent_id' => 'parent category',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->route('id');
            
            // If setting a parent_id, ensure it's not a subcategory itself
            if ($this->has('parent_id') && $this->parent_id) {
                $parentCategory = \App\Models\Category::find($this->parent_id);
                
                if ($parentCategory && $parentCategory->parent_id !== null) {
                    $validator->errors()->add('parent_id', 'Selected parent category must be a main category, not a subcategory.');
                }
                
                // Check for circular reference (prevent A->B->A scenarios)
                if ($this->wouldCreateCircularReference($categoryId, $this->parent_id)) {
                    $validator->errors()->add('parent_id', 'This would create a circular reference in the category hierarchy.');
                }
            }
        });
    }

    /**
     * Check if setting parent_id would create circular reference
     */
    private function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        $currentParent = \App\Models\Category::find($parentId);
        
        while ($currentParent) {
            if ($currentParent->id === $categoryId) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }
        
        return false;
    }
}