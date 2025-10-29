<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequests;
use App\Services\PromoService;
use Exception;
use Inertia\Inertia;
use App\Models\Meal;
use App\Models\MenuItem;
use App\Models\PromoScope;

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
                'data' => $promo
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promo status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPromosForItem($itemId)
{
    try {
        $currentTime = now('Asia/Karachi');
        $currentDate = $currentTime->toDateString();
        $currentTimeOnly = $currentTime->format('H:i');

        // Find the menu item with its meals
        $menuItem = MenuItem::with(['meals'])->findOrFail($itemId);
        
        $activeMealIds = $menuItem->meals->filter(function ($meal) use ($currentTimeOnly) {

            if (empty($meal->start_time) || empty($meal->end_time)) {
                \Log::warning('Meal has missing time values', [
                    'meal_id' => $meal->id,
                    'start_time' => $meal->start_time,
                    'end_time' => $meal->end_time
                ]);
                return false;
            }

            // Get start and end times
            $startTime = trim($meal->start_time);
            $endTime = trim($meal->end_time);

            // Compare times
            $isActive = $currentTimeOnly >= $startTime && $currentTimeOnly <= $endTime;
            
            \Log::info('Checking meal time', [
                'meal_id' => $meal->id,
                'meal_name' => $meal->name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'current' => $currentTimeOnly,
                'is_active' => $isActive
            ]);

            return $isActive;
        })->pluck('id');

        // If no active meals, return empty with detailed debug info
        if ($activeMealIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No active meals for this item at current time',
                'current_time' => $currentTimeOnly,
                'debug_meals' => $menuItem->meals->map(function($meal) {
                    return [
                        'id' => $meal->id,
                        'name' => $meal->name ?? 'N/A',
                        'start_time' => $meal->start_time,
                        'end_time' => $meal->end_time
                    ];
                })
            ]);
        }

        // Get promo scopes
        $promoScopes = PromoScope::query()
            ->where(function ($query) use ($itemId, $activeMealIds) {
                $query->whereHas('menuItems', function ($q) use ($itemId) {
                    $q->where('menu_item_id', $itemId);
                })
                ->orWhereHas('meals', function ($q) use ($activeMealIds) {
                    $q->whereIn('meal_id', $activeMealIds);
                });
            })
            ->with(['promos' => function ($query) use ($currentDate) {
                $query->where('status', 'active')
                    ->where('start_date', '<=', $currentDate)
                    ->where('end_date', '>=', $currentDate);
            }])
            ->get();

        $promos = $promoScopes->pluck('promos')
            ->flatten()
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $promos,
            'active_meals' => $activeMealIds,
            'current_time' => $currentTimeOnly
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching promos for item: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch promos: ' . $e->getMessage()
        ], 500);
    }
}

}
