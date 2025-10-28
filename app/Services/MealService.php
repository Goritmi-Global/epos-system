<?php

namespace App\Services;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MealService
{
    /**
     * Get all meals
     */
    public function getAllMeals(array $filters = []): Collection
    {
        $query = Meal::query()->latest();

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }

    /**
     * Get currently active meals
     */
    public function getActiveMeals(): Collection
    {
        return Meal::active()->get();
    }

    /**
     * Get paginated meals
     */
    public function getPaginatedMeals(int $perPage = 15): LengthAwarePaginator
    {
        return Meal::latest()->paginate($perPage);
    }

    /**
     * Create a new meal
     */
    public function createMeal(array $data): Meal
    {
        return Meal::create([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);
    }

    /**
     * Update an existing meal
     */
    public function updateMeal(int $id, array $data): Meal
    {
        $meal = Meal::findOrFail($id);

        $meal->update([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return $meal->fresh();
    }

    /**
     * Delete a meal
     */
    public function deleteMeal(int $id): bool
    {
        $meal = Meal::findOrFail($id);
        return $meal->delete();
    }

    /**
     * Get a single meal by ID
     */
    public function getMealById(int $id): Meal
    {
        return Meal::findOrFail($id);
    }
}