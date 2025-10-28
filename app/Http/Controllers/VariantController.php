<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVariantRequest;
use App\Http\Requests\UpdateVariantRequest;
use App\Services\VariantService;
use App\Services\VariantGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VariantController extends Controller
{
    protected $variantService;
    protected $variantGroupService;

    /**
     * Inject services via constructor
     */
    public function __construct(
        VariantService $variantService,
        VariantGroupService $variantGroupService
    ) {
        $this->variantService = $variantService;
        $this->variantGroupService = $variantGroupService;
    }

    /**
     * Display the variants management page
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        return inertia('Variant/Variants');
    }

    /**
     * Get all variants (API endpoint for Vue)
     * 
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $variants = $this->variantService->getAllVariants();
            
            return response()->json([
                'success' => true,
                'data' => $variants
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch variants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variants by group ID
     * 
     * @param int $groupId
     * @return JsonResponse
     */
    public function byGroup($groupId): JsonResponse
    {
        try {
            $variants = $this->variantService->getVariantsByGroup($groupId);
            
            return response()->json([
                'success' => true,
                'data' => $variants
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch variants',
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
            $stats = $this->variantService->getStatistics();
            
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
     * Store a new variant
     * 
     * @param StoreVariantRequest $request
     * @return JsonResponse
     */
    public function store(StoreVariantRequest $request): JsonResponse
    {
        try {
            $variant = $this->variantService->createVariant($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Variant created successfully',
                'data' => $variant
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific variant
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $variant = $this->variantService->getVariantById($id);
            
            return response()->json([
                'success' => true,
                'data' => $variant
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing variant
     * 
     * @param StoreVariantRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateVariantRequest $request, $id): JsonResponse
    {
        try {
            $variant = $this->variantService->updateVariant($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Variant updated successfully',
                'data' => $variant
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a variant
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->variantService->deleteVariant($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Variant deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle variant status
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

            $variant = $this->variantService->toggleStatus($id, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $variant
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
     * Update sort order for multiple variants
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sort_data' => 'required|array',
                'sort_data.*.id' => 'required|exists:variants,id',
                'sort_data.*.sort_order' => 'required|integer|min:0'
            ]);

            $this->variantService->updateSortOrder($request->sort_data);
            
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

    /**
     * Validate variant selection for a group
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateSelection(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'variant_group_id' => 'required|exists:variant_groups,id',
                'variant_ids' => 'required|array',
                'variant_ids.*' => 'exists:variants,id'
            ]);

            $result = $this->variantService->validateSelection(
                $request->variant_group_id,
                $request->variant_ids
            );
            
            return response()->json([
                'success' => $result['valid'],
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate total price modifier for selected variants
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculatePriceModifier(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'variant_ids' => 'required|array',
                'variant_ids.*' => 'exists:variants,id'
            ]);

            $total = $this->variantService->calculateTotalPriceModifier($request->variant_ids);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_modifier' => $total,
                    'formatted' => '£' . number_format($total, 2)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate price modifier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}