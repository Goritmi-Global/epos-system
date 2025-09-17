<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreCategoryRequest;
use App\Http\Requests\Reference\UpdateCategoryRequest;
use App\Models\InventoryCategory;
use App\Services\Reference\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if ($request->has('search')) {
                $categories = $this->categoryService->searchCategories($request->search);
            } else {
                $categories = $this->categoryService->getAllCategories();
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get parent categories for dropdown
     */
    public function getParents(): JsonResponse
    {
        try {
            $parentCategories = $this->categoryService->getParentCategories();

            return response()->json([
                'success' => true,
                'data' => $parentCategories,
                'message' => 'Parent categories retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve parent categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store newly created categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $result = $this->categoryService->createCategories($request->validated());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => $result['message'],
                    'count' => $result['count'],
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            if (! $category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->categoryService->updateCategory($id, $request->validated());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => $result['message'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->categoryService->deleteCategory($id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->categoryService->getCategoryStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle category active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $result = $this->categoryService->toggleCategoryStatus($id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'message' => $result['message'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle category status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSubcategory(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = $this->categoryService->updateSubcategoryName($id, $request->input('name'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $categories = $request->input('categories', []);

       
        foreach ($categories as $row) {
            $parentName = $row['category'] ?? null;
            $subName = $row['subcategory'] ?? null;

            // 1. Create/find parent
            $parent = null;
            if ($parentName) {
                $parent = InventoryCategory::firstOrCreate(
                    ['name' => $parentName, 'parent_id' => null],
                    ['icon' => $row['icon'] ?? "", 'active' => $row['active'] ?? 1]
                );
            }

            // 2. Create/find subcategory
            if ($subName) {
                InventoryCategory::firstOrCreate(
                    ['name' => $subName, 'parent_id' => $parent?->id],
                    ['icon' => $row['icon'] ?? "", 'active' => $row['active'] ?? 1]
                );
            }
        }

        return response()->json(['message' => 'Categories imported successfully']);
    }
}
