<?php

namespace App\Services\POS;

use App\Helpers\UploadHelper;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuCategoryService
{
    public function createCategories(array $data): array
    {
        try {
            DB::beginTransaction();

            $createdCategories = [];
            $isSubCategory = $data['isSubCategory'] ?? false;
            $uploadId = null;
            if (! empty($data['icon'])) {
                $upload = UploadHelper::store($data['icon'], 'menu-category-icons', 'public');
                $uploadId = $upload->id;
            }

            if (! empty($data['categories'])) {
                foreach ($data['categories'] as $cat) {
                    if ($isSubCategory) {
                        if (empty($cat['parent_id'])) {
                            throw new Exception('Subcategory must have a parent_id');
                        }

                        $parent = MenuCategory::where('id', $cat['parent_id'])
                            ->whereNull('parent_id')
                            ->first();

                        if (! $parent) {
                            throw new Exception('Invalid parent category');
                        }
                    } else {
                        if (! empty($cat['parent_id'])) {
                            throw new Exception('Parent category cannot have parent_id');
                        }
                    }
                    $existing = MenuCategory::where('name', $cat['name'])
                        ->where('parent_id', $cat['parent_id'] ?? null)
                        ->first();

                    if ($existing) {
                        continue;
                    }
                    $category = $this->createSingleCategory([
                        'name' => $cat['name'],
                        'upload_id' => $uploadId,
                        'active' => $cat['active'] ?? true,
                        'parent_id' => $cat['parent_id'] ?? null,
                    ]);

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

            return [
                'success' => false,
                'message' => 'Failed to create categories: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    private function createSingleCategory(array $categoryData): MenuCategory
    {
        return MenuCategory::create([
            'name' => $categoryData['name'],
            'upload_id' => $categoryData['upload_id'] ?? null,
            'active' => $categoryData['active'] ?? true,
            'parent_id' => $categoryData['parent_id'] ?? null,
            'total_value' => 0,
            'total_items' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
            'in_stock' => 0,
        ]);
    }

    /**
     * Get all categories with their subcategories
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCategories()
    {
        return MenuCategory::with(['subcategories'])
            ->get()
            ->map(function ($cat) {
                $cat->image_url = UploadHelper::url($cat->upload_id);
                if ($cat->subcategories) {
                    $cat->subcategories->each(function ($sub) {
                        $sub->image_url = UploadHelper::url($sub->upload_id);
                    });
                }

                return $cat;
            });
    }


    public function getParentCategories(array $filters = [])
    {
        $query = MenuCategory::with(['subcategories', 'parent'])
            ->withCount('menuItems')
            ->whereNull('parent_id');
        $totalCount = MenuCategory::whereNull('parent_id')->count();
        $activeCount = MenuCategory::whereNull('parent_id')->where('active', 1)->count();
        $inactiveCount = MenuCategory::whereNull('parent_id')->where('active', 0)->count();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', "%{$filters['q']}%");
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('active', 1);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('active', 0);
            }
        }
        if (! empty($filters['category'])) {
            $query->where('id', $filters['category']);
        }
        if (! empty($filters['has_subcategories'])) {
            if ($filters['has_subcategories'] === 'yes') {
                $query->whereHas('subcategories');
            } elseif ($filters['has_subcategories'] === 'no') {
                $query->doesntHave('subcategories');
            }
        }
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'items_desc':
                    $query->orderByRaw('(SELECT COUNT(*) FROM menu_items WHERE menu_items.category_id = menu_categories.id) DESC');
                    break;
                case 'items_asc':
                    $query->orderByRaw('(SELECT COUNT(*) FROM menu_items WHERE menu_items.category_id = menu_categories.id) ASC');
                    break;
                default:
                    $query->orderBy('id', 'DESC');
                    break;
            }
        } else {
            $query->orderBy('id', 'DESC');
        }
        $searchQuery = trim($filters['q'] ?? '');
        $hasSearch = ! empty($searchQuery);
        $hasStatus = ! empty($filters['status']);
        $hasCategory = ! empty($filters['category']);
        $hasSubcategoriesFilter = ! empty($filters['has_subcategories']);
        $hasSorting = ! empty($filters['sort_by']);

        $hasAnyFilter = $hasSearch || $hasStatus || $hasCategory
                     || $hasSubcategoriesFilter || $hasSorting;

        Log::info('Menu Category Filter Debug', [
            'hasAnyFilter' => $hasAnyFilter,
            'filters' => $filters,
        ]);

        if ($hasAnyFilter) {
            $allCategories = $query->get();
            $total = $allCategories->count();

            Log::info('Filter Mode (Menu Categories)', ['total_found' => $total]);
            $transformedCategories = $allCategories->map(function ($category) {
                return $this->transformCategory($category);
            });
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedCategories,
                $total,
                $total > 0 ? $total : 1,
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

        } else {
            Log::info('Pagination Mode (Menu Categories)');

            $perPage = $filters['per_page'] ?? 10;
            $paginator = $query->paginate($perPage);

            $paginator->getCollection()->transform(function ($category) {
                return $this->transformCategory($category);
            });
        }
        $response = $paginator->toArray();
        $response['stats'] = [
            'total' => $totalCount,
            'active' => $activeCount,
            'inactive' => $inactiveCount,
        ];

        return $response;
    }

    private function transformCategory($category)
    {
        $category->total_menu_items = $category->menu_items_count;
        $category->image_url = UploadHelper::url($category->upload_id);

        if ($category->subcategories) {
            $category->subcategories->each(function ($sub) {
                $sub->image_url = UploadHelper::url($sub->upload_id);
            });
        }

        return $category;
    }

    public function getCategoryById(int $id): ?MenuCategory
    {
        return MenuCategory::with(['subcategories', 'parent'])->find($id);
    }

    /**
     * Update category
     */

    // public function updateCategory(int $id, array $data): array
    // {
    //     try {
    //         $category = MenuCategory::find($id);

    //         if (!$category) {
    //             return [
    //                 'success' => false,
    //                 'message' => 'Category not found',
    //                 'data' => null
    //             ];
    //         }

    //         // Validate parent category if updating to subcategory
    //         if (isset($data['parent_id']) && $data['parent_id']) {
    //             $parentCategory = MenuCategory::find($data['parent_id']);
    //             if (!$parentCategory) {
    //                 throw new Exception('Parent category not found');
    //             }

    //             // Prevent circular reference
    //             if ($data['parent_id'] == $id) {
    //                 throw new Exception('Category cannot be its own parent');
    //             }
    //         }

    //         //  UPDATE THE MAIN CATEGORY FIRST
    //         $category->update([
    //             'name' => $data['name'] ?? $category->name,
    //             'icon' => $data['icon'] ?? $category->icon,
    //             'active' => $data['active'] ?? $category->active,
    //             'parent_id' => $data['parent_id'] ?? $category->parent_id,
    //         ]);

    //         //  THEN HANDLE SUBCATEGORIES IF PROVIDED
    //         if (isset($data['subcategories']) && is_array($data['subcategories'])) {
    //             // Get current subcategories
    //             $currentSubcategoryIds = $category->subcategories()->pluck('id')->toArray();
    //             $updatedSubcategoryIds = [];

    //             foreach ($data['subcategories'] as $subData) {
    //                 if (isset($subData['id']) && $subData['id']) {
    //                     // Update existing subcategory
    //                     $subcategory = MenuCategory::find($subData['id']);
    //                     if ($subcategory && $subcategory->parent_id == $category->id) {
    //                         $subcategory->update([
    //                             'name' => $subData['name'],
    //                             'active' => $subData['active'] ?? true,
    //                             'icon' => $data['icon'] ?? $subcategory->icon, // Inherit parent icon
    //                         ]);
    //                         $updatedSubcategoryIds[] = $subData['id'];
    //                     }
    //                 } else {
    //                     // Create new subcategory
    //                     $newSubcategory = MenuCategory::create([
    //                         'name' => $subData['name'],
    //                         'parent_id' => $category->id,
    //                         'icon' => $data['icon'] ?? null, // Inherit parent icon
    //                         'active' => $subData['active'] ?? true,
    //                     ]);
    //                     $updatedSubcategoryIds[] = $newSubcategory->id;
    //                 }
    //             }

    //             //  DELETE subcategories that were removed from the list
    //             $subcategoriesToDelete = array_diff($currentSubcategoryIds, $updatedSubcategoryIds);
    //             if (!empty($subcategoriesToDelete)) {
    //                 MenuCategory::whereIn('id', $subcategoriesToDelete)
    //                     ->where('parent_id', $category->id) // Extra safety check
    //                     ->delete();
    //             }
    //         }

    //         // Reload category with subcategories
    //         $updatedCategory = MenuCategory::with('subcategories')->find($id);

    //         return [
    //             'success' => true,
    //             'message' => 'Category updated successfully',
    //             'data' => $updatedCategory
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'data' => null
    //         ];
    //     }
    // }

    public function updateCategory(int $id, array $data): array
    {
        try {
            DB::beginTransaction();

            $category = MenuCategory::find($id);
            if (! $category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ];
            }

            // Handle new image upload if provided
            $uploadId = $category->upload_id;
            if (! empty($data['icon'])) {
                $upload = UploadHelper::store($data['icon'], 'menu-category-icons', 'public');
                $uploadId = $upload->id;

                // Delete old file if exists
                if ($category->upload_id) {
                    UploadHelper::delete($category->upload_id);
                }
            }

            $category->update([
                'name' => $data['name'] ?? $category->name,
                'upload_id' => $uploadId,
                'active' => isset($data['active'])
                    ? (int) $data['active']
                    : (int) $category->active,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
            ]);

            DB::commit();

            $updated = MenuCategory::with(['subcategories', 'parent'])->find($id);

            return [
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $updated,
            ];
        } catch (Exception $e) {
            DB::rollback();

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

            $category = MenuCategory::find($id);

            if (! $category) {
                return [
                    'success' => false,
                    'message' => 'Category not found',
                    'data' => null,
                ];
            }

            // ❗ Check if category is in use by menu items
            if ($category->menuItems()->count() > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete. Category is already used in menu(s).',
                    'data' => null,
                ];
            }

            // ❗ Check if any subcategory is also used
            $subCategoryIds = MenuCategory::where('parent_id', $id)->pluck('id');

            $usedSubCategories = MenuItem::whereIn('category_id', $subCategoryIds)->count();

            if ($usedSubCategories > 0) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete. One or more subcategories are used in menu items.',
                    'data' => null,
                ];
            }

            // Delete subcategories
            MenuCategory::where('parent_id', $id)->delete();

            // Delete main category
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
    public function searchCategories(string $query)
    {
        return MenuCategory::with(['subcategories', 'parent'])
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(): array
    {
        $totalCategories = MenuCategory::count();
        $parentCategories = MenuCategory::whereNull('parent_id')->count();
        $subcategories = MenuCategory::whereNotNull('parent_id')->count();
        $activeCategories = MenuCategory::where('active', true)->count();
        $inactiveCategories = MenuCategory::where('active', false)->count();

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
            $category = MenuCategory::find($id);

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
            $subcategory = MenuCategory::find($subcategoryId);

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
            $duplicate = MenuCategory::where('name', $newName)
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
}
