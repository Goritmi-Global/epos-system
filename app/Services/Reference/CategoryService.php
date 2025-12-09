<?php

namespace App\Services\Reference;

use App\Helpers\UploadHelper;
use App\Models\InventoryCategory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    /**
     * Create categories (both parent and subcategories)
     *
     * @throws Exception
     */
    public function createCategories(array $data): array
    {
        try {
            DB::beginTransaction();

            $createdCategories = [];
            $isSubCategory = $data['isSubCategory'] ?? false;

            // Handle icon upload ONCE for all categories in this batch
            $uploadId = null;
            if (! empty($data['icon'])) {
                $upload = UploadHelper::store($data['icon'], 'category-icons', 'public');
                $uploadId = $upload->id;
                Log::info('Category icon uploaded', [
                    'upload_id' => $uploadId,
                    'original_name' => $data['icon']->getClientOriginalName(),
                ]);
            }

            if (! empty($data['categories'])) {
                foreach ($data['categories'] as $cat) {
                    // Validate based on isSubCategory flag
                    if ($isSubCategory) {
                        // Creating subcategory - must have parent_id
                        if (empty($cat['parent_id'])) {
                            Log::error('Subcategory missing parent_id:', $cat);
                            throw new Exception('Subcategory must have a parent_id');
                        }

                        // Verify parent exists and is a main category
                        $parent = InventoryCategory::where('id', $cat['parent_id'])
                            ->whereNull('parent_id')
                            ->first();

                        if (! $parent) {
                            Log::error('Invalid parent category:', $cat['parent_id']);
                            throw new Exception('Invalid parent category');
                        }
                    } else {
                        // Creating parent category - must NOT have parent_id
                        if (! empty($cat['parent_id'])) {
                            Log::error('Parent category has parent_id:', $cat);
                            throw new Exception('Parent category cannot have parent_id');
                        }
                    }

                    // Check for duplicates
                    $existingCategory = InventoryCategory::where('name', $cat['name'])
                        ->where('parent_id', $cat['parent_id'] ?? null)
                        ->first();

                    if ($existingCategory) {
                        Log::info('Category already exists, skipping:', $cat['name']);

                        continue;
                    }

                    // Create the category with upload_id
                    $category = $this->createSingleCategory([
                        'name' => $cat['name'],
                        'upload_id' => $uploadId, // Use upload_id instead of icon
                        'active' => $cat['active'] ?? true,
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
            Log::error('Category creation failed: '.$e->getMessage());
            Log::error('Stack trace:', [$e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Failed to create categories: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Create a single category
     *
     * @return Category
     */
    private function createSingleCategory(array $categoryData): InventoryCategory
    {
        $category = InventoryCategory::create([
            'name' => $categoryData['name'],
            'upload_id' => $categoryData['upload_id'] ?? null, // Store upload_id instead of icon
            'active' => $categoryData['active'] ?? true,
            'parent_id' => $categoryData['parent_id'] ?? null,
            'total_value' => 0,
            'total_items' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
            'in_stock' => 0,
        ]);

        return $category;
    }

    /**
     * Get all categories with their subcategories
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCategories(array $filters = [])
    {
        $query = InventoryCategory::with([
            'subcategories' => function ($query) {
                $query->withCount('primaryInventoryItems');
            },
        ])
            ->withCount('primaryInventoryItems')
            ->whereNull('parent_id');

        // Apply search filter if provided
        if (! empty($filters['q'])) {
            $s = $filters['q'];
            $query->where('name', 'like', "%{$s}%");
        }

        // Get paginated results
        $paginatedResults = $query->latest()->paginate($filters['per_page'] ?? 15);

        // Transform the data
        $paginatedResults->getCollection()->transform(function ($category) {
            // Add total count for parent
            $category->total_inventory_items = $category->primary_inventory_items_count;

            // Add image URL
            $category->image_url = UploadHelper::url($category->upload_id);

            // Add image URL for subcategories too
            if ($category->subcategories) {
                $category->subcategories->each(function ($sub) {
                    $sub->image_url = UploadHelper::url($sub->upload_id);
                });
            }

            return $category;
        });

        return $paginatedResults;
    }

    /**
     * Get only parent categories (for dropdown)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getParentCategories()
    {
        return InventoryCategory::whereNull('parent_id')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $category->image_url = UploadHelper::url($category->upload_id);

                return $category;
            });
    }

    /**
     * Get category by ID with relationships
     *
     * @return Category|null
     */
    public function getCategoryById(int $id): ?InventoryCategory
    {
        $category = InventoryCategory::with(['subcategories', 'parent'])->find($id);

        if ($category) {
            $category->image_url = UploadHelper::url($category->upload_id);

            if ($category->subcategories) {
                $category->subcategories->each(function ($sub) {
                    $sub->image_url = UploadHelper::url($sub->upload_id);
                });
            }

            if ($category->parent) {
                $category->parent->image_url = UploadHelper::url($category->parent->upload_id);
            }
        }

        return $category;
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $category = InventoryCategory::find($id);

            if (! $category) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ];
            }

            // Validate parent category if updating to subcategory
            if (isset($data['parent_id']) && $data['parent_id']) {
                $parentCategory = InventoryCategory::find($data['parent_id']);
                if (! $parentCategory) {
                    throw new Exception('Parent category not found');
                }

                // Prevent circular reference
                if ($data['parent_id'] == $id) {
                    throw new Exception('Category cannot be its own parent');
                }
            }

            // Handle icon upload if provided
            $uploadId = $category->upload_id; // Keep existing by default
            if (! empty($data['icon'])) {
                $upload = UploadHelper::store($data['icon'], 'category-icons', 'public');
                $uploadId = $upload->id;

                // Optional: Delete old upload if exists
                if ($category->upload_id) {
                    UploadHelper::delete($category->upload_id);
                }
            }

            // UPDATE THE MAIN CATEGORY FIRST
            $category->update([
                'name' => $data['name'] ?? $category->name,
                'upload_id' => $uploadId,
                'active' => $data['active'] ?? $category->active,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
            ]);

            // THEN HANDLE SUBCATEGORIES IF PROVIDED
            if (isset($data['subcategories']) && is_array($data['subcategories'])) {
                // Get current subcategories
                $currentSubcategoryIds = $category->subcategories()->pluck('id')->toArray();
                $updatedSubcategoryIds = [];

                foreach ($data['subcategories'] as $subData) {
                    if (isset($subData['id']) && $subData['id']) {
                        // Update existing subcategory
                        $subcategory = InventoryCategory::find($subData['id']);
                        if ($subcategory && $subcategory->parent_id == $category->id) {
                            $subcategory->update([
                                'name' => $subData['name'],
                                'active' => $subData['active'] ?? true,
                                'upload_id' => $uploadId, // Inherit parent icon
                            ]);
                            $updatedSubcategoryIds[] = $subData['id'];
                        }
                    } else {
                        // Create new subcategory
                        $newSubcategory = InventoryCategory::create([
                            'name' => $subData['name'],
                            'parent_id' => $category->id,
                            'upload_id' => $uploadId, // Inherit parent icon
                            'active' => $subData['active'] ?? true,
                        ]);
                        $updatedSubcategoryIds[] = $newSubcategory->id;
                    }
                }

                // DELETE subcategories that were removed from the list
                $subcategoriesToDelete = array_diff($currentSubcategoryIds, $updatedSubcategoryIds);
                if (! empty($subcategoriesToDelete)) {
                    InventoryCategory::whereIn('id', $subcategoriesToDelete)
                        ->where('parent_id', $category->id)
                        ->delete();
                }
            }

            DB::commit();

            // Reload category with subcategories and upload
            $updatedCategory = InventoryCategory::with(['subcategories.upload', 'upload'])->find($id);

            return [
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $updatedCategory,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): array
    {
        try {
            DB::beginTransaction();

            $category = InventoryCategory::find($id);

            if (! $category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ];
            }

            // Delete icon upload if exists
            if ($category->upload_id) {
                UploadHelper::delete($category->upload_id);
            }

            // Delete all subcategories of this category
            InventoryCategory::where('parent_id', $id)->delete();

            // Delete the parent category
            $category->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Category and its subcategories deleted successfully',
                'data' => null,
            ];
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Category deletion failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to delete category: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Search categories by name
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchCategories(string $query, array $filters = [])
    {
        $queryBuilder = InventoryCategory::with(['subcategories', 'parent'])
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name');

        return $queryBuilder->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(): array
    {
        $totalCategories = InventoryCategory::count();
        $parentCategories = InventoryCategory::whereNull('parent_id')->count();
        $subcategories = InventoryCategory::whereNotNull('parent_id')->count();
        $activeCategories = InventoryCategory::where('active', true)->count();
        $inactiveCategories = InventoryCategory::where('active', false)->count();

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
     */
    public function toggleCategoryStatus(int $id): array
    {
        try {
            $category = InventoryCategory::find($id);

            if (! $category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ];
            }

            $category->active = ! $category->active;
            $category->save();

            return [
                'success' => true,
                'message' => 'Category status updated successfully',
                'data' => $category,
            ];
        } catch (Exception $e) {
            Log::error('Category status toggle failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to update category status: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Update only the subcategory name
     */
    public function updateSubcategoryName(int $subcategoryId, string $newName): array
    {
        try {
            $subcategory = InventoryCategory::find($subcategoryId);

            if (! $subcategory || ! $subcategory->parent_id) {
                return [
                    'success' => false,
                    'message' => 'Subcategory not found or it is not a subcategory',
                    'data' => null,
                ];
            }

            $parentId = $subcategory->parent_id;

            $newName = trim($newName);

            if (empty($newName)) {
                return [
                    'success' => false,
                    'message' => 'Subcategory name cannot be empty',
                    'data' => null,
                ];
            }

            // Check for duplicates under the same parent
            $duplicate = InventoryCategory::where('name', $newName)
                ->where('parent_id', $parentId)
                ->where('id', '!=', $subcategoryId)
                ->exists();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "The subcategory '{$newName}' already exists under this category.",
                    'data' => null,
                ];
            }

            // Update subcategory
            $subcategory->update(['name' => $newName]);

            return [
                'success' => true,
                'message' => 'Subcategory updated successfully',
                'data' => $subcategory,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function getAllCategoriesWithFilters(array $filters = [])
    {
        $query = InventoryCategory::with([
            'subcategories' => function ($query) {
                $query->withCount('primaryInventoryItems');
            },
        ])
            ->withCount('primaryInventoryItems')
            ->whereNull('parent_id');

        // Search filter
        if (! empty($filters['q'])) {
            $query->where('name', 'like', "%{$filters['q']}%");
        }

        // Status filter (active/inactive)
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('active', 1);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('active', 0);
            }
        }

        // Has Subcategories filter
        if (! empty($filters['has_subcategories'])) {
            if ($filters['has_subcategories'] === 'yes') {
                $query->whereHas('subcategories');
            } elseif ($filters['has_subcategories'] === 'no') {
                $query->doesntHave('subcategories');
            }
        }

        // Stock Status filter
        if (! empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $query->where('in_stock', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('low_stock', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('out_of_stock', '>', 0);
                    break;
                case 'has_items':
                    $query->where('total_items', '>', 0);
                    break;
                case 'no_items':
                    $query->where('total_items', 0);
                    break;
            }
        }

        // Value range filter
        if (! empty($filters['value_min']) && $filters['value_min'] !== '') {
            $query->where('total_value', '>=', $filters['value_min']);
        }

        if (! empty($filters['value_max']) && $filters['value_max'] !== '') {
            $query->where('total_value', '<=', $filters['value_max']);
        }

        // Sorting
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'value_desc':
                    $query->orderBy('total_value', 'desc');
                    break;
                case 'value_asc':
                    $query->orderBy('total_value', 'asc');
                    break;
                case 'items_desc':
                    $query->orderByRaw('primary_inventory_items_count DESC');
                    break;
                case 'items_asc':
                    $query->orderByRaw('primary_inventory_items_count ASC');
                    break;
                default:
                    $query->orderBy('name', 'asc');
                    break;
            }
        } else {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $paginatedResults = $query->paginate($perPage);

        // Transform data
        $paginatedResults->getCollection()->transform(function ($category) {
            $category->total_inventory_items = $category->primary_inventory_items_count;
            $category->image_url = UploadHelper::url($category->upload_id);

            if ($category->subcategories) {
                $category->subcategories->each(function ($sub) {
                    $sub->image_url = UploadHelper::url($sub->upload_id);
                });
            }

            return $category;
        });

        return $paginatedResults;
    }
}
