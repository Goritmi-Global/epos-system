<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonRequest;
use App\Services\AddonService;
use App\Services\AddonGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddonController extends Controller
{
    protected $addonService;
    protected $addonGroupService;

    /**
     * Inject services via constructor
     */
    public function __construct(
        AddonService $addonService,
        AddonGroupService $addonGroupService
    ) {
        $this->addonService = $addonService;
        $this->addonGroupService = $addonGroupService;
    }

    /**
     * Display the addons management page
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        return inertia('Addons/Addons');
    }

    /**
     * Get all addons (API endpoint for Vue)
     * 
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $addons = $this->addonService->getAllAddons();
            
            return response()->json([
                'success' => true,
                'data' => $addons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get addons by group ID
     * 
     * @param int $groupId
     * @return JsonResponse
     */
    public function byGroup($groupId): JsonResponse
    {
        try {
            $addons = $this->addonService->getAddonsByGroup($groupId);
            
            return response()->json([
                'success' => true,
                'data' => $addons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addons',
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
            $stats = $this->addonService->getStatistics();
            
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
     * Store a new addon
     * 
     * @param StoreAddonRequest $request
     * @return JsonResponse
     */
    public function store(StoreAddonRequest $request): JsonResponse
    {
        try {
            $addon = $this->addonService->createAddon($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Addon created successfully',
                'data' => $addon
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create addon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific addon
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $addon = $this->addonService->getAddonById($id);
            
            return response()->json([
                'success' => true,
                'data' => $addon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Addon not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing addon
     * 
     * @param StoreAddonRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreAddonRequest $request, $id): JsonResponse
    {
        try {
            $addon = $this->addonService->updateAddon($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Addon updated successfully',
                'data' => $addon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update addon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an addon
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->addonService->deleteAddon($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Addon deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle addon status
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

            $addon = $this->addonService->toggleStatus($id, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $addon
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
     * Update sort order for multiple addons
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sort_data' => 'required|array',
                'sort_data.*.id' => 'required|exists:addons,id',
                'sort_data.*.sort_order' => 'required|integer|min:0'
            ]);

            $this->addonService->updateSortOrder($request->sort_data);
            
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
