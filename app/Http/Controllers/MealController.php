<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Services\MealService;
use Exception;
use Inertia\Inertia;

class MealController extends Controller
{
    protected MealService $mealService;

    public function __construct(MealService $mealService)
    {
        $this->mealService = $mealService;
    }

    /**
     * Display a listing of meals
     */
    public function index()
    {
        return Inertia::render('Backend/Meal/Index');
    }

    /**
     * Fetch all meals via API
     */
    public function fetchAllMeals()
    {
        try {
            $meals = $this->mealService->getAllMeals();

            return response()->json([
                'success' => true,
                'data' => $meals,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch meals: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get currently active meals
     */
    public function getActiveMeals()
    {
        try {
            $meals = $this->mealService->getActiveMeals();

            return response()->json([
                'success' => true,
                'data' => $meals,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active meals: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created meal
     */
    public function store(StoreMealRequest $request)
    {
        try {
            $this->mealService->createMeal($request->validated());

            return redirect()->back()->with('success', 'Meal created successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create meal: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified meal
     */
    public function show(int $id)
    {
        try {
            $meal = $this->mealService->getMealById($id);

            return inertia('Backend/Meal/Index', [
                'meal' => $meal,
            ]);
        } catch (Exception $e) {
            return redirect()->route('meals.index')
                ->with('error', 'Meal not found');
        }
    }

    /**
     * Update the specified meal
     */
    public function update(UpdateMealRequest $request, int $id)
    {
        try {
            $this->mealService->updateMeal($id, $request->validated());

            return redirect()->back()->with('success', 'Meal updated successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update meal: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified meal
     */
    public function destroy(int $id)
    {
        try {
            $this->mealService->deleteMeal($id);

            return redirect()->back()->with('success', 'Meal deleted successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete meal: ' . $e->getMessage());
        }
    }
}