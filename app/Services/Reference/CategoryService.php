<?php

namespace App\Services\Reference;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryService
{
    /**
     * Create categories (both parent and subcategories)
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createCategories(array $data): array
    {
        try {
            DB::beginTransaction();

            $createdCategories = [];
            $isSubCategory = $data['isSubCategory'] ?? false;

            // Add detailed logging
            Log::info('Creating categories with data:', $data);

            if (!empty($data['categories'])) {
                foreach ($data['categories'] as $cat) {
                    // Validate based on isSubCategory flag
                    if ($isSubCategory) {
                        // Creating subcategory - must have parent_id
                        if (empty($cat['parent_id'])) {
                            Log::error('Subcategory missing parent_id:', $cat);
                            throw new Exception('Subcategory must have a parent_id');
                        }
                        
                        // Verify parent exists and is a main category
                        $parent = Category::where('id', $cat['parent_id'])
                            ->whereNull('parent_id')
                            ->first();
                            
                        if (!$parent) {
                            Log::error('Invalid parent category:', $cat['parent_id']);
                            throw new Exception('Invalid parent category');
                        }
                    } else {
                        // Creating parent category - must NOT have parent_id
                        if (!empty($cat['parent_id'])) {
                            Log::error('Parent category has parent_id:', $cat);
                            throw new Exception('Parent category cannot have parent_id');
                        }
                    }

                    // Check for duplicates
                    $existingCategory = Category::where('name', $cat['name'])
                        ->where('parent_id', $cat['parent_id'] ?? null)
                        ->first();

                    if ($existingCategory) {
                        Log::info('Category already exists, skipping:', $cat['name']);
                        continue;
                    }

                    // Create the category
                    $category = $this->createSingleCategory([
                        'name'      => $cat['name'],
                        'icon'      => $cat['icon'] ?? '🧰',
                        'active'    => $cat['active'] ?? true,
                        'parent_id' => $cat['parent_id'] ?? null,
                    ]);
                    
                    Log::info('Created category:', $category->toArray());
                    $createdCategories[] = $category;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Categories created successfully',
                'data' => $createdCategories,
                'count' => count($createdCategories),
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Category creation failed: ' . $e->getMessage());
            Log::error('Stack trace:', [$e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Failed to create categories: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }



    /**
     * Create a single category
     *
     * @param array $categoryData
     * @return Category
     */
     private function createSingleCategory(array $categoryData): Category
    {
        Log::info('Creating single category with data:', $categoryData);
        
        $category = Category::create([
            'name' => $categoryData['name'],
            'icon' => $categoryData['icon'] ?? '🧰',
            'active' => $categoryData['active'] ?? true,
            'parent_id' => $categoryData['parent_id'],
            'total_value' => 0,
            'total_items' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
            'in_stock' => 0,
        ]);
        
        Log::info('Successfully created category:', $category->toArray());
        return $category;
    }

    /**
     * Get all categories with their subcategories
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCategories()
    {
        return Category::with(['subcategories', 'parent'])->get();
    }

    /**
     * Get only parent categories (for dropdown)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getParentCategories()
    {
        return Category::whereNull('parent_id')->orderBy('name')->get();
    }

    /**
     * Get category by ID with relationships
     *
     * @param int $id
     * @return Category|null
     */
    public function getCategoryById(int $id): ?Category
    {
        return Category::with(['subcategories', 'parent'])->find($id);
    }

    /**
     * Update category
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            $category = Category::find($id);
            
            if (!$category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null
                ];
            }

            // Validate parent category if updating to subcategory
            if (isset($data['parent_id']) && $data['parent_id']) {
                $parentCategory = Category::find($data['parent_id']);
                if (!$parentCategory) {
                    throw new Exception('Parent category not found');
                }
                
                // Prevent circular reference
                if ($data['parent_id'] == $id) {
                    throw new Exception('Category cannot be its own parent');
                }
            }

            $category->update([
                'name' => $data['name'] ?? $category->name,
                'icon' => $data['icon'] ?? $category->icon,
                'active' => $data['active'] ?? $category->active,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
            ]);

            return [
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh(['subcategories', 'parent'])
            ];

        } catch (Exception $e) {
            Log::error('Category update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Delete category
     *
     * @param int $id
     * @return array
     */
    public function deleteCategory(int $id): array
{
    try {
        DB::beginTransaction();
        
        $category = Category::find($id);
        
        if (!$category) {
            return [
                'success' => false,
                'message' => 'Category not found',
                'data' => null
            ];
        }

        // Delete all subcategories of this category
        Category::where('parent_id', $id)->delete();

        // Delete the parent category
        $category->delete();
        
        DB::commit();

        return [
            'success' => true,
            'message' => 'Category and its subcategories deleted successfully',
            'data' => null
        ];

    } catch (Exception $e) {
        DB::rollback();
        Log::error('Category deletion failed: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Failed to delete category: ' . $e->getMessage(),
            'data' => null
        ];
    }
}


    /**
     * Search categories by name
     *
     * @param string $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchCategories(string $query)
    {
        return Category::with(['subcategories', 'parent'])
            ->where('name', 'like', '%' . $query . '%')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get category statistics
     *
     * @return array
     */
    public function getCategoryStatistics(): array
    {
        $totalCategories = Category::count();
        $parentCategories = Category::whereNull('parent_id')->count();
        $subcategories = Category::whereNotNull('parent_id')->count();
        $activeCategories = Category::where('active', true)->count();
        $inactiveCategories = Category::where('active', false)->count();

        return [
            'total_categories' => $totalCategories,
            'parent_categories' => $parentCategories,
            'subcategories' => $subcategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
        ];
    }

    /**
     * Toggle category active status
     *
     * @param int $id
     * @return array
     */
    public function toggleCategoryStatus(int $id): array
    {
        try {
            $category = Category::find($id);
            
            if (!$category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null
                ];
            }

            $category->active = !$category->active;
            $category->save();

            return [
                'success' => true,
                'message' => 'Category status updated successfully',
                'data' => $category
            ];

        } catch (Exception $e) {
            Log::error('Category status toggle failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update category status: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}