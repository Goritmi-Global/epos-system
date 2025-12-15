<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequests;
use App\Models\Meal;
use App\Models\Promo;
use App\Models\PromoScope;
use App\Services\PromoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

  public function fetchAllPromos(Request $request)
{
    try {
        $filters = [
            'q' => $request->query('q', ''),
            'status' => $request->query('status', ''),
            'category' => $request->query('category', ''),
            'sort_by' => $request->query('sort_by', ''),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => $request->query('per_page', 10),
        ];

        if ($request->has('export') && $request->export === 'all') {
            $query = Promo::query();
            
            if (!empty($filters['q'])) {
                $query->where('name', 'like', "%{$filters['q']}%");
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['category'])) {
                $query->where('type', $filters['category']);
            }
            if (isset($filters['price_min']) && $filters['price_min'] !== null) {
                $query->where('discount_amount', '>=', (float) $filters['price_min']);
            }
            if (isset($filters['price_max']) && $filters['price_max'] !== null) {
                $query->where('discount_amount', '<=', (float) $filters['price_max']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('start_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('start_date', '<=', $filters['date_to']);
            }
            if (!empty($filters['sort_by'])) {
                switch ($filters['sort_by']) {
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'discount_asc':
                        $query->orderBy('discount_amount', 'asc');
                        break;
                    case 'discount_desc':
                        $query->orderBy('discount_amount', 'desc');
                        break;
                    case 'date_asc':
                        $query->orderBy('start_date', 'asc');
                        break;
                    case 'date_desc':
                        $query->orderBy('start_date', 'desc');
                        break;
                }
            }

            $allPromos = $query->get();

            return response()->json([
                'success' => true,
                'data' => $allPromos,
                'total' => $allPromos->count(),
            ]);
        }

        $promos = $this->promoService->getAllPromos($filters);

        return response()->json([
            'success' => true,
            'data' => $promos->items(),
            'pagination' => [
                'current_page' => $promos->currentPage(),
                'last_page' => $promos->lastPage(),
                'per_page' => $promos->perPage(),
                'total' => $promos->total(),
                'from' => $promos->firstItem(),
                'to' => $promos->lastItem(),
                'links' => $promos->linkCollection()->toArray(),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch promos: '.$e->getMessage(),
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
                'message' => 'Failed to fetch promos: '.$e->getMessage(),
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
                ->withErrors(['error' => 'Failed to create promo: '.$e->getMessage()])
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
                ->withErrors(['error' => 'Failed to update promo: '.$e->getMessage()])
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
                ->with('error', 'Failed to delete promo: '.$e->getMessage());
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
                'message' => 'Failed to update promo status: '.$e->getMessage(),
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
            })->filter(fn ($p) => $p['promo']);

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
            \Log::error('Error fetching promos for item: '.$e->getMessage(), [
                'item_id' => $itemId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: '.$e->getMessage(),
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
                'meals:id,name',
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
                    if (! $description) {
                        if ($promo->type === 'flat') {
                            $description = 'Get $'.number_format($promo->discount_amount, 2).' off your order';
                        } else {
                            $description = 'Save '.$promo->discount_amount.'% on eligible items';
                        }

                        if ($promo->min_purchase > 0) {
                            $description .= ' (min purchase $'.number_format($promo->min_purchase, 2).')';
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
            \Log::error('Error fetching current meal promos: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch current meal promos: '.$e->getMessage(),
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
                'meals:id,name',
            ])->get();

            $promos = $promoScopes->flatMap(function ($scope) {
                return $scope->promos->map(function ($promo) use ($scope) {
                    $description = $promo->description;
                    if (! $description) {
                        if ($promo->type === 'flat') {
                            $description = 'Get $'.number_format($promo->discount_amount, 2).' off your order';
                        } else {
                            $description = 'Save '.$promo->discount_amount.'% on eligible items';
                        }

                        if ($promo->min_purchase > 0) {
                            $description .= ' (min purchase $'.number_format($promo->min_purchase, 2).')';
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
            \Log::error('Error fetching all promos: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos',
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        try {
            $promos = $request->input('promos', []);

            if (empty($promos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No promos data provided',
                ], 422);
            }

            $importedCount = 0;
            $errors = [];

            foreach ($promos as $index => $row) {
                $name = $row['name'] ?? null;
                $type = strtolower($row['type'] ?? 'percent');
                $discountAmount = $row['discount_amount'] ?? null;
                $startDate = $row['start_date'] ?? null;
                $endDate = $row['end_date'] ?? null;
                $minPurchase = $row['min_purchase'] ?? 0;
                $maxDiscount = $row['max_discount'] ?? null;
                $status = strtolower($row['status'] ?? 'active');
                $description = $row['description'] ?? null;

                // Validate required fields
                if (! $name || ! $discountAmount || ! $startDate || ! $endDate) {
                    $errors[] = 'Row '.($index + 1).': Missing required fields (name, discount_amount, start_date, end_date)';

                    continue;
                }

                // Validate type
                if (! in_array($type, ['flat', 'percent'])) {
                    $errors[] = 'Row '.($index + 1).": Invalid type. Must be 'flat' or 'percent'";

                    continue;
                }

                // Validate status
                if (! in_array($status, ['active', 'inactive'])) {
                    $errors[] = 'Row '.($index + 1).": Invalid status. Must be 'active' or 'inactive'";

                    continue;
                }

                // Validate dates
                try {
                    // Handle multiple date formats
                    $startDateObj = $this->parseDate($startDate);
                    $endDateObj = $this->parseDate($endDate);

                    if (! $startDateObj || ! $endDateObj) {
                        $errors[] = 'Row '.($index + 1).': Invalid date format. Use YYYY-MM-DD format';

                        continue;
                    }

                    if ($endDateObj < $startDateObj) {
                        $errors[] = 'Row '.($index + 1).': End date must be after start date';

                        continue;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Row '.($index + 1).': Invalid date format: '.$e->getMessage();

                    continue;
                }

                // Validate discount amount
                if (! is_numeric($discountAmount) || $discountAmount <= 0) {
                    $errors[] = 'Row '.($index + 1).': Discount amount must be a positive number';

                    continue;
                }

                // Validate percentage discount
                if ($type === 'percent' && $discountAmount > 100) {
                    $errors[] = 'Row '.($index + 1).': Percentage discount cannot exceed 100%';

                    continue;
                }

                // Validate min purchase
                if (! is_numeric($minPurchase) || $minPurchase < 0) {
                    $errors[] = 'Row '.($index + 1).': Minimum purchase must be a non-negative number';

                    continue;
                }

                // Validate max discount (if provided)
                if ($maxDiscount !== null && $maxDiscount !== '' && (! is_numeric($maxDiscount) || $maxDiscount <= 0)) {
                    $errors[] = 'Row '.($index + 1).': Maximum discount must be a positive number';

                    continue;
                }

                try {
                    // Create promo using service
                    $this->promoService->createPromo([
                        'name' => trim($name),
                        'type' => $type,
                        'discount_amount' => $discountAmount,
                        'start_date' => $startDateObj->format('Y-m-d'),
                        'end_date' => $endDateObj->format('Y-m-d'),
                        'min_purchase' => $minPurchase,
                        'max_discount' => $maxDiscount && $maxDiscount !== '' ? $maxDiscount : null,
                        'status' => $status,
                        'description' => $description,
                    ]);

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = 'Row '.($index + 1).': '.$e->getMessage();

                    continue;
                }
            }

            // Prepare response
            if ($importedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid promos were imported',
                    'errors' => $errors,
                ], 422);
            }

            $responseMessage = "Successfully imported {$importedCount} promo(s)";
            if (! empty($errors)) {
                $responseMessage .= ' with '.count($errors).' error(s)';
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'imported_count' => $importedCount,
                'errors' => $errors,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import promos: '.$e->getMessage(),
            ], 500);
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // If already a DateTime object, return it
        if ($date instanceof \DateTime) {
            return $date;
        }

        // Clean up the date string
        $date = trim($date);

        // PRIMARY: Try ISO format first (YYYY-MM-DD) - this should match your frontend output
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj !== false && $dateObj->format('Y-m-d') === $date) {
            $dateObj->setTime(0, 0, 0);

            return $dateObj;
        }

        // Try ISO format with time (YYYY-MM-DD HH:ii:ss)
        $dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if ($dateObj !== false) {
            $dateObj->setTime(0, 0, 0);

            return $dateObj;
        }

        // Try other formats
        $formats = [
            'Y/m/d',           // 2025/06/01
            'd/m/Y',           // 01/06/2025
            'm/d/Y',           // 06/01/2025
            'd-m-Y',           // 01-06-2025
            'm-d-Y',           // 06-01-2025
        ];

        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false && $dateObj->format($format) === $date) {
                $dateObj->setTime(0, 0, 0);

                return $dateObj;
            }
        }

        // Handle slash format with intelligent parsing
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $year = (int) $matches[3];

            $format = ($first > 12) ? 'd/m/Y' : 'm/d/Y';
            $dateObj = \DateTime::createFromFormat($format, $date);

            if ($dateObj !== false) {
                $dateObj->setTime(0, 0, 0);

                return $dateObj;
            }
        }

        // Handle dash format with intelligent parsing
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $date, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];

            $format = ($first > 12) ? 'd-m-Y' : 'm-d-Y';
            $dateObj = \DateTime::createFromFormat($format, $date);

            if ($dateObj !== false) {
                $dateObj->setTime(0, 0, 0);

                return $dateObj;
            }
        }

        // Last resort: strtotime with validation
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            $dateObj = new \DateTime;
            $dateObj->setTimestamp($timestamp);
            $dateObj->setTime(0, 0, 0);

            if ($dateObj->format('Y') >= 2000 && $dateObj->format('Y') <= 2100) {
                return $dateObj;
            }
        }

        return null;
    }
}
