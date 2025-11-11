<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonGroupRequest;
use App\Models\AddonGroup;
use App\Services\AddonGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function all(): JsonResponse
    {
        try {
            $groups = $this->addonGroupService->getAllGroups();

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addon groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for KPI cards
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->addonGroupService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new addon group
     */
    public function store(StoreAddonGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->addonGroupService->createGroup($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Addon group created successfully',
                'data' => $group,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create addon group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific addon group
     *
     * @param  int  $id
     */
    public function show($id): JsonResponse
    {
        try {
            $group = $this->addonGroupService->getGroupById($id);

            return response()->json([
                'success' => true,
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Addon group not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update an existing addon group
     *
     * @param  int  $id
     */
    public function update(StoreAddonGroupRequest $request, $id): JsonResponse
    {
        try {
            $group = $this->addonGroupService->updateGroup($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Addon group updated successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update addon group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an addon group
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->addonGroupService->deleteGroup($id);

            return response()->json([
                'success' => true,
                'message' => 'Addon group deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle addon group status
     *
     * @param  int  $id
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,inactive',
            ]);

            $group = $this->addonGroupService->toggleStatus($id, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only active groups (for dropdowns)
     */
    public function active(): JsonResponse
    {
        try {
            $groups = $this->addonGroupService->getActiveGroups();

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
      
        try {
            $groups = $request->input('groups', []);
        

            if (empty($groups)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No addon groups data provided',
                ], 422);
            }

            $importedCount = 0;
            $skipped = 0;

            foreach ($groups as $index => $groupData) {
                try {
                    // Validate required fields
                    if (empty($groupData['name'])) {
                        $skipped++;

                        continue;
                    }

                    // Parse numeric values
                    $minSelect = (int) ($groupData['min_select'] ?? 0);
                    $maxSelect = (int) ($groupData['max_select'] ?? 1);
                    $status = strtolower($groupData['status'] ?? 'active');
                    $description = $groupData['description'] ?? '';

                    // Validate min <= max
                    if ($minSelect > $maxSelect) {
                        $skipped++;

                        continue;
                    }

                    // Validate status
                    if (! in_array($status, ['active', 'inactive'])) {
                        $status = 'active';
                    }

                    // Check if group already exists
                    $exists = AddonGroup::where('name', $groupData['name'])->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    // Create the group
                    $this->addonGroupService->createGroup([
                        'name' => trim($groupData['name']),
                        'min_select' => $minSelect,
                        'max_select' => $maxSelect,
                        'status' => $status,
                        'description' => trim($description),
                    ]);

                    $importedCount++;

                } catch (\Exception $e) {
                    $skipped++;

                    continue;
                }
            }

            // Check if any were imported
            if ($importedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid addon groups were imported. Please check your data.',
                ], 422);
            }

            // Build success message
            $message = "Successfully imported {$importedCount} addon group(s)";
            if ($skipped > 0) {
                $message .= " ({$skipped} skipped)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skipped,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import addon groups: '.$e->getMessage(),
            ], 500);
        }
    }
}
