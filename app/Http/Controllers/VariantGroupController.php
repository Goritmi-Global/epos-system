<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVariantGroupRequest;
use App\Http\Requests\UpdateVariantGroupRequest;
use App\Services\VariantGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VariantGroupController extends Controller
{
    protected $variantGroupService;

    /**
     * Inject the service via constructor
     */
    public function __construct(VariantGroupService $variantGroupService)
    {
        $this->variantGroupService = $variantGroupService;
    }

    /**
     * Display the variant groups management page
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        return inertia('Variant/VariantGroups');
    }

    /**
     * Get all variant groups (API endpoint for Vue)
     * 
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $groups = $this->variantGroupService->getAllGroups();
            
            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch variant groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for KPI cards
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->variantGroupService->getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new variant group
     * 
     * @param StoreVariantGroupRequest $request
     * @return JsonResponse
     */
    public function store(StoreVariantGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->variantGroupService->createGroup($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Variant group created successfully',
                'data' => $group
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create variant group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific variant group
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $group = $this->variantGroupService->getGroupById($id);
            
            return response()->json([
                'success' => true,
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variant group not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing variant group
     * 
     * @param StoreVariantGroupRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateVariantGroupRequest $request, $id): JsonResponse
    {
        try {
            $group = $this->variantGroupService->updateGroup($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Variant group updated successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variant group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a variant group
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->variantGroupService->deleteGroup($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Variant group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle variant group status
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,inactive'
            ]);

            $group = $this->variantGroupService->toggleStatus($id, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only active groups (for dropdowns)
     * 
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        try {
            $groups = $this->variantGroupService->getActiveGroups();
            
            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sort order for multiple variant groups
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sort_data' => 'required|array',
                'sort_data.*.id' => 'required|exists:variant_groups,id',
                'sort_data.*.sort_order' => 'required|integer|min:0'
            ]);

            $this->variantGroupService->updateSortOrder($request->sort_data);
            
            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sort order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}