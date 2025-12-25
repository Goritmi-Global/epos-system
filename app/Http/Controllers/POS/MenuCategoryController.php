<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuCategoryRequest;
use App\Http\Requests\Menu\UpdateMenuCategoryRequest;
use App\Models\MenuCategory;
use App\Services\POS\MenuCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuCategoryController extends Controller
{
    protected MenuCategoryService $MenuCategoryService;

    public function __construct(MenuCategoryService $MenuCategoryService)
    {
        $this->MenuCategoryService = $MenuCategoryService;
    }

    /**
     * Display a listing of the categories
     */
    public function index(Request $request)
    {
        try {
            if ($request->has('search')) {
                $categories = $this->MenuCategoryService->searchCategories($request->search);
            } else {
                $categories = $this->MenuCategoryService->getAllCategories();
            }

            return Inertia::render('Backend/Menu/Category', [
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

  public function getParentsForDropdown(): JsonResponse
   {
       try {
           $parentCategories = MenuCategory::whereNull('parent_id')
               ->where('active', 1)
               ->orderBy('name', 'asc')
               ->get(['id', 'name']);

           return response()->json([
               'success' => true,
               'data' => $parentCategories,
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
     * ✅ UPDATED: Original getParents method with fixed filter detection
     */
    public function getParents(Request $request): JsonResponse
    {
        try {
            $filters = [];

            if ($request->has('q') && trim($request->query('q')) !== '') {
                $filters['q'] = trim($request->query('q'));
            }

            if ($request->has('sort_by') && $request->query('sort_by') !== '') {
                $filters['sort_by'] = $request->query('sort_by');
            }

            if ($request->has('status') && $request->query('status') !== '') {
                $filters['status'] = $request->query('status');
            }

            if ($request->has('category') && $request->query('category') !== '') {
                $filters['category'] = $request->query('category');
            }

            if ($request->has('has_subcategories') && $request->query('has_subcategories') !== '') {
                $filters['has_subcategories'] = $request->query('has_subcategories');
            }

            if ($request->has('per_page')) {
                $filters['per_page'] = $request->query('per_page', 10);
            }

            $parentCategories = $this->MenuCategoryService->getParentCategories($filters);

            return response()->json($parentCategories);
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
    public function store(StoreMenuCategoryRequest $request): JsonResponse
    {
        try {
            $result = $this->MenuCategoryService->createCategories($request->validated());

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
            $category = $this->MenuCategoryService->getCategoryById($id);

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
    public function update(UpdateMenuCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->MenuCategoryService->updateCategory($id, $request->validated());
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
            $result = $this->MenuCategoryService->deleteCategory($id);

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
            $stats = $this->MenuCategoryService->getCategoryStatistics();

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
            $result = $this->MenuCategoryService->toggleCategoryStatus($id);

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

        $result = $this->MenuCategoryService->updateSubcategoryName($id, $request->input('name'));

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
                $parent = MenuCategory::firstOrCreate(
                    ['name' => $parentName, 'parent_id' => null],
                    ['icon' => $row['icon'] ?? '', 'active' => $row['active'] ?? 1]
                );
            }

            // 2. Create/find subcategory
            if ($subName) {
                MenuCategory::firstOrCreate(
                    ['name' => $subName, 'parent_id' => $parent?->id],
                    ['icon' => $row['icon'] ?? '', 'active' => $row['active'] ?? 1]
                );
            }
        }

        return response()->json(['message' => 'Categories imported successfully']);
    }
}
