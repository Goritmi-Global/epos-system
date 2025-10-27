<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonGroupRequest;
use App\Services\AddonGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddonGroupController extends Controller
{
    protected $addonGroupService;

    /**
     * Inject the service via constructor
     */
    public function __construct(AddonGroupService $addonGroupService)
    {
        $this->addonGroupService = $addonGroupService;
    }

    /**
     * Display the addon groups management page
     * 
     * @return \Inertia\Response
     */
    public function index()
    {
        return inertia('Addons/AddonGroups');
    }

    /**
     * Get all addon groups (API endpoint for Vue)
     * 
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            $groups = $this->addonGroupService->getAllGroups();
            
            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addon groups',
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
            $stats = $this->addonGroupService->getStatistics();
            
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
     * Store a new addon group
     * 
     * @param StoreAddonGroupRequest $request
     * @return JsonResponse
     */
    public function store(StoreAddonGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->addonGroupService->createGroup($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Addon group created successfully',
                'data' => $group
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create addon group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific addon group
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $group = $this->addonGroupService->getGroupById($id);
            
            return response()->json([
                'success' => true,
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Addon group not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing addon group
     * 
     * @param StoreAddonGroupRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreAddonGroupRequest $request, $id): JsonResponse
    {
        try {
            $group = $this->addonGroupService->updateGroup($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Addon group updated successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update addon group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an addon group
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->addonGroupService->deleteGroup($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Addon group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle addon group status
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

            $group = $this->addonGroupService->toggleStatus($id, $request->status);
            
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
            $groups = $this->addonGroupService->getActiveGroups();
            
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
}
