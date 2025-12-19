<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonRequest;
use App\Http\Requests\UpdateAddonRequest;
use App\Models\Addon;
use App\Models\AddonGroup;
use App\Services\AddonGroupService;
use App\Services\AddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function all(Request $request): JsonResponse
    {
        try {
            $filters = [
                'q' => $request->query('q', ''),
                'status' => $request->query('status', ''),
                'category' => $request->query('category', ''),
                'sort_by' => $request->query('sort_by', ''),
                'price_min' => $request->query('price_min'),
                'price_max' => $request->query('price_max'),
                'per_page' => $request->query('per_page', 10),
            ];
            if ($request->has('export') && $request->export === 'all') {
                $query = Addon::with('addonGroup');
                if (! empty($filters['q'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['q']}%")
                            ->orWhereHas('addonGroup', function ($subQ) use ($filters) {
                                $subQ->where('name', 'like', "%{$filters['q']}%");
                            });
                    });
                }
                if (! empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (! empty($filters['category'])) {
                    $query->where('addon_group_id', $filters['category']);
                }
                if (isset($filters['price_min']) && $filters['price_min'] !== null) {
                    $query->where('price', '>=', (float) $filters['price_min']);
                }
                if (isset($filters['price_max']) && $filters['price_max'] !== null) {
                    $query->where('price', '<=', (float) $filters['price_max']);
                }
                if (! empty($filters['sort_by'])) {
                    switch ($filters['sort_by']) {
                        case 'name_asc':
                            $query->orderBy('name', 'asc');
                            break;
                        case 'name_desc':
                            $query->orderBy('name', 'desc');
                            break;
                        case 'price_asc':
                            $query->orderBy('price', 'asc');
                            break;
                        case 'price_desc':
                            $query->orderBy('price', 'desc');
                            break;
                        case 'newest':
                            $query->orderBy('created_at', 'desc');
                            break;
                        case 'oldest':
                            $query->orderBy('created_at', 'asc');
                            break;
                    }
                }

                $allAddons = $query->get();

                return response()->json([
                    'success' => true,
                    'data' => $allAddons,
                    'total' => $allAddons->count(),
                ]);
            }
            $addons = $this->addonService->getAllAddons($filters);

            return response()->json([
                'success' => true,
                'data' => $addons->items(),
                'pagination' => [
                    'current_page' => $addons->currentPage(),
                    'last_page' => $addons->lastPage(),
                    'per_page' => $addons->perPage(),
                    'total' => $addons->total(),
                    'from' => $addons->firstItem(),
                    'to' => $addons->lastItem(),
                    'links' => $addons->linkCollection()->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addons',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get addons by group ID
     *
     * @param  int  $groupId
     */
    public function byGroup($groupId): JsonResponse
    {
        try {
            $addons = $this->addonService->getAddonsByGroup($groupId);

            return response()->json([
                'success' => true,
                'data' => $addons,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addons',
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
            $stats = $this->addonService->getStatistics();

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
     * Store a new addon
     */
    public function store(StoreAddonRequest $request): JsonResponse
    {
        try {
            $addon = $this->addonService->createAddon($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Addon created successfully',
                'data' => $addon,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create addon',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific addon
     *
     * @param  int  $id
     */
    public function show($id): JsonResponse
    {
        try {
            $addon = $this->addonService->getAddonById($id);

            return response()->json([
                'success' => true,
                'data' => $addon,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Addon not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update an existing addon
     *
     * @param  int  $id
     */
    public function update(UpdateAddonRequest $request, $id): JsonResponse
    {
        try {
            $addon = $this->addonService->updateAddon($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Addon updated successfully',
                'data' => $addon,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update addon',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an addon
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->addonService->deleteAddon($id);

            return response()->json([
                'success' => true,
                'message' => 'Addon deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle addon status
     *
     * @param  int  $id
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,inactive',
            ]);

            $addon = $this->addonService->toggleStatus($id, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $addon,
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
     * Update sort order for multiple addons
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sort_data' => 'required|array',
                'sort_data.*.id' => 'required|exists:addons,id',
                'sort_data.*.sort_order' => 'required|integer|min:0',
            ]);

            $this->addonService->updateSortOrder($request->sort_data);

            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sort order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        try {
            $addons = $request->input('addons', []);

            if (empty($addons)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No addons data provided',
                ], 422);
            }

            $importedCount = 0;
            $skipped = 0;

            foreach ($addons as $index => $addonData) {
                try {
                    // Validate required fields
                    if (empty($addonData['name']) || empty($addonData['addon_group_name'])) {
                        $skipped++;

                        continue;
                    }

                    // Parse numeric value
                    $price = (float) ($addonData['price'] ?? 0);
                    $status = strtolower($addonData['status'] ?? 'active');
                    $description = $addonData['description'] ?? '';

                    // Validate price
                    if ($price < 0) {
                        $skipped++;

                        continue;
                    }

                    // Validate status
                    if (! in_array($status, ['active', 'inactive'])) {
                        $status = 'active';
                    }

                    // Find addon group by name
                    $addonGroup = AddonGroup::where('name', trim($addonData['addon_group_name']))->first();
                    if (! $addonGroup) {
                        $skipped++;

                        continue;
                    }

                    // Check if addon already exists in this group
                    $exists = Addon::where('name', trim($addonData['name']))
                        ->where('addon_group_id', $addonGroup->id)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    // Create the addon
                    $this->addonService->createAddon([
                        'name' => trim($addonData['name']),
                        'addon_group_id' => $addonGroup->id,
                        'price' => $price,
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
                    'message' => 'No valid addons were imported. Please check your data.',
                ], 422);
            }

            // Build success message
            $message = "Successfully imported {$importedCount} addon(s)";
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
                'message' => 'Failed to import addons: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getUniqueGroups(Request $request): JsonResponse
    {
        try {
            $query = Addon::with('addonGroup')
                ->whereHas('addonGroup', function ($q) {
                    $q->where('status', 1);
                });

            // Apply same filters as main query
            if ($request->filled('q')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%")
                        ->orWhereHas('addonGroup', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', "%{$request->q}%");
                        });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('price_min')) {
                $query->where('price', '>=', (float) $request->price_min);
            }

            if ($request->filled('price_max')) {
                $query->where('price', '<=', (float) $request->price_max);
            }

            $uniqueGroups = $query->get()
                ->pluck('addonGroup')
                ->unique('id')
                ->filter() // Remove nulls
                ->values()
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $uniqueGroups,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unique groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
