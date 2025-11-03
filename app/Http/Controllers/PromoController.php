<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequests;
use App\Models\Meal;
use App\Models\PromoScope;
use App\Services\PromoService;
use Exception;
use Inertia\Inertia;

class PromoController extends Controller
{
    protected PromoService $promoService;

    public function __construct(PromoService $promoService)
    {
        $this->promoService = $promoService;
    }

    /**
     * Display a listing of promos
     */
    public function index()
    {
        $meals = Meal::with('menuItems')->get();

        return Inertia::render('Backend/Promo/Index', [
            'meals' => $meals,
        ]);
    }

    public function fetchAllPromos()
    {
        try {
            $promos = $this->promoService->getAllPromos();

            return response()->json([
                'success' => true,
                'data' => $promos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's active promos
     */
    public function getTodayPromos()
    {
        try {
            $promos = $this->promoService->getTodayPromos();

            return response()->json([
                'success' => true,
                'data' => $promos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created promo
     */
    public function store(StorePromoRequest $request)
    {

        try {
            $this->promoService->createPromo($request->validated());

            return redirect()->back()->with('success', 'Promo created successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create promo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified promo
     */
    public function show(int $id)
    {
        try {
            $promo = $this->promoService->getPromoById($id);

            return inertia('Backend/Promo/Index', [
                'promo' => $promo,
            ]);
        } catch (Exception $e) {
            return redirect()->route('promos.index')
                ->with('error', 'Promo not found');
        }
    }

    /**
     * Update the specified promo
     */
    public function update(UpdatePromoRequests $request, int $id)
    {
        try {
            $this->promoService->updatePromo($id, $request->validated());

            return redirect()->back()->with('success', 'Promo updated successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update promo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified promo
     */
    public function destroy(int $id)
    {
        try {
            $this->promoService->deletePromo($id);

            return redirect()->back()->with('success', 'Promo deleted successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete promo: ' . $e->getMessage());
        }
    }

    /**
     * Toggle promo status
     */
    public function toggleStatus(int $id)
    {
        try {
            $promo = $this->promoService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Promo status updated successfully',
                'data' => $promo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promo status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPromosForItem($itemId = null)
    {
        try {
            $currentTime = now('Asia/Karachi');
            $currentDate = $currentTime->toDateString();
            $currentTimeOnly = $currentTime->format('H:i');

            // --- Step 1: Determine active meals for current time ---
            $activeMealIds = Meal::whereTime('start_time', '<=', $currentTimeOnly)
                ->whereTime('end_time', '>=', $currentTimeOnly)
                ->pluck('id');

            if ($activeMealIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No active meals found at this time.',
                    'current_time' => $currentTimeOnly,
                ]);
            }

            // --- Step 2: Build base PromoScope query ---
            $promoQuery = PromoScope::whereHas('meals', function ($q) use ($activeMealIds) {
                $q->whereIn('meal_id', $activeMealIds);
            })
                ->with([
                    'promos' => function ($q) use ($currentDate) {
                        $q->where('status', 'active')
                            ->whereDate('start_date', '<=', $currentDate)
                            ->whereDate('end_date', '>=', $currentDate);
                    },
                    'menuItems:id,name,price',
                ]);

            // --- Step 3: If a specific item ID is given, filter for it ---
            if ($itemId) {
                $promoQuery->whereHas('menuItems', function ($q) use ($itemId) {
                    $q->where('menu_item_id', $itemId);
                });
            }

            $promoScopes = $promoQuery->get();

            // --- Step 4: Flatten promos & remove duplicates ---
            $promos = $promoScopes->pluck('promos')->flatten()->unique('id')->values();

            // --- Step 5: Map promos to their applicable items ---
            $promosWithItems = $promoScopes->map(function ($scope) {
                return [
                    'promo' => $scope->promos->first(),
                    'menu_items' => $scope->menuItems,
                ];
            })->filter(fn($p) => $p['promo']);

            \Log::info('Fetched current-time promos', [
                'item_id' => $itemId,
                'active_meal_ids' => $activeMealIds,
                'promo_count' => $promos->count(),
                'current_time' => $currentTimeOnly,
            ]);

            return response()->json([
                'success' => true,
                'data' => $itemId ? $promos : $promosWithItems,
                'active_meals' => $activeMealIds,
                'current_time' => $currentTimeOnly,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching promos for item: ' . $e->getMessage(), [
                'item_id' => $itemId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getCurrentMealPromos()
    {
        try {
            $currentTime = now('Asia/Karachi');
            $currentDate = $currentTime->toDateString();
            $currentTimeOnly = $currentTime->format('H:i');

            // Find current active meal
            $currentMeal = Meal::where('start_time', '<=', $currentTimeOnly)
                ->where('end_time', '>=', $currentTimeOnly)
                ->first();

            // Fetch ALL active promos (not just meal-specific)
            $promoScopes = PromoScope::with([
                'promos' => function ($q) use ($currentDate) {
                    $q->where('status', 'active')
                        ->whereDate('start_date', '<=', $currentDate)
                        ->whereDate('end_date', '>=', $currentDate)
                        ->orderBy('discount_amount', 'desc'); // Show best deals first
                },
                'menuItems:id,name',
                'meals:id,name'
            ]);

            // If there's a current meal, prioritize meal-specific promos
            if ($currentMeal) {
                $promoScopes->where(function ($query) use ($currentMeal) {
                    $query->whereHas('meals', function ($q) use ($currentMeal) {
                        $q->where('meal_id', $currentMeal->id);
                    })->orWhereDoesntHave('meals'); // Include promos without meal restrictions
                });
            }

            $promoScopes = $promoScopes->get();

            // Flatten promos and attach linked menu items
            $promos = $promoScopes->flatMap(function ($scope) {
                return $scope->promos->map(function ($promo) use ($scope) {
                    // Build description if not set
                    $description = $promo->description;
                    if (!$description) {
                        if ($promo->type === 'flat') {
                            $description = "Get $" . number_format($promo->discount_amount, 2) . " off your order";
                        } else {
                            $description = "Save " . $promo->discount_amount . "% on eligible items";
                        }

                        if ($promo->min_purchase > 0) {
                            $description .= " (min purchase $" . number_format($promo->min_purchase, 2) . ")";
                        }
                    }

                    return [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'description' => $description,
                        'type' => $promo->type,
                        'discount_amount' => $promo->discount_amount,
                        'min_purchase' => $promo->min_purchase ?? 0,
                        'max_discount' => $promo->max_discount,
                        'status' => $promo->status,
                        'start_date' => $promo->start_date,
                        'end_date' => $promo->end_date,
                        'menu_items' => $scope->menuItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                            ];
                        })->values(),
                        'meals' => $scope->meals->map(function ($meal) {
                            return [
                                'id' => $meal->id,
                                'name' => $meal->name,
                            ];
                        })->values(),
                    ];
                });
            })->unique('id')->values();

            return response()->json([
                'success' => true,
                'data' => $promos,
                'meal' => $currentMeal ? $currentMeal->only(['id', 'name', 'start_time', 'end_time']) : null,
                'current_time' => $currentTimeOnly,
                'total_promos' => $promos->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching current meal promos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch current meal promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Optional: Add endpoint to get ALL promos (not meal-restricted)
    public function getAllPromos()
    {
        try {
            $currentDate = now('Asia/Karachi')->toDateString();

            $promoScopes = PromoScope::with([
                'promos' => function ($q) use ($currentDate) {
                    $q->where('status', 'active')
                        ->whereDate('start_date', '<=', $currentDate)
                        ->whereDate('end_date', '>=', $currentDate)
                        ->orderBy('discount_amount', 'desc');
                },
                'menuItems:id,name',
                'meals:id,name'
            ])->get();

            $promos = $promoScopes->flatMap(function ($scope) {
                return $scope->promos->map(function ($promo) use ($scope) {
                    $description = $promo->description;
                    if (!$description) {
                        if ($promo->type === 'flat') {
                            $description = "Get $" . number_format($promo->discount_amount, 2) . " off your order";
                        } else {
                            $description = "Save " . $promo->discount_amount . "% on eligible items";
                        }

                        if ($promo->min_purchase > 0) {
                            $description .= " (min purchase $" . number_format($promo->min_purchase, 2) . ")";
                        }
                    }

                    return [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'description' => $description,
                        'type' => $promo->type,
                        'discount_amount' => $promo->discount_amount,
                        'min_purchase' => $promo->min_purchase ?? 0,
                        'max_discount' => $promo->max_discount,
                        'status' => $promo->status,
                        'start_date' => $promo->start_date,
                        'end_date' => $promo->end_date,
                        'menu_items' => $scope->menuItems->map(function ($item) {
                            return ['id' => $item->id, 'name' => $item->name];
                        })->values(),
                        'meals' => $scope->meals->map(function ($meal) {
                            return ['id' => $meal->id, 'name' => $meal->name];
                        })->values(),
                    ];
                });
            })->unique('id')->values();

            return response()->json([
                'success' => true,
                'data' => $promos,
                'total_promos' => $promos->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching all promos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos',
            ], 500);
        }
    }
}
