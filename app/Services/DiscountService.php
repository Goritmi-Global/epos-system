<?php

namespace App\Services;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DiscountService
{
    /**
     * Get all discounts with optional filters
     * 
     * @param array $filters - Optional filters (status, type, search)
     * @return Collection
     */
    public function getAllDiscounts(array $filters = []): Collection
    {
        $query = Discount::query()->latest();

        // Filter by status if provided
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by type (flat or percent) if provided
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Search by name if provided
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }

    /**
     * Get today's active discounts
     * 
     * @return Collection
     */
    public function getTodayDiscounts(): Collection
    {
        return Discount::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest()
            ->get();
    }

    /**
     * Get paginated discounts
     * 
     * @param int $perPage - Number of records per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedDiscounts(int $perPage = 15): LengthAwarePaginator
    {
        return Discount::latest()->paginate($perPage);
    }

    /**
     * Create a new discount
     * 
     * @param array $data - Discount data
     * @return Discount
     */
    public function createDiscount(array $data): Discount
    {
        return Discount::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'min_purchase' => $data['min_purchase'],
            'max_discount' => $data['max_discount'] ?? null,
            'description' => $data['description'] ?? null,
            'discount_amount' => $data['discount_amount'],
        ]);
    }

    /**
     * Update an existing discount
     * 
     * @param int $id - Discount ID
     * @param array $data - Updated data
     * @return Discount
     */
    public function updateDiscount(int $id, array $data): Discount
    {
        $discount = Discount::findOrFail($id);

        $discount->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'discount_amount' => $data['discount_amount'],
            'status' => $data['status'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'min_purchase' => $data['min_purchase'],
            'max_discount' => $data['max_discount'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return $discount->fresh();
    }

    /**
     * Delete a discount
     * 
     * @param int $id - Discount ID
     * @return bool
     */
    public function deleteDiscount(int $id): bool
    {
        $discount = Discount::findOrFail($id);
        return $discount->delete();
    }

    /**
     * Get a single discount by ID
     * 
     * @param int $id - Discount ID
     * @return Discount
     */
    public function getDiscountById(int $id): Discount
    {
        return Discount::findOrFail($id);
    }

    /**
     * Toggle discount status between active and inactive
     * 
     * @param int $id - Discount ID
     * @return Discount
     */
    public function toggleStatus(int $id): Discount
    {
        $discount = Discount::findOrFail($id);
        $discount->status = $discount->status === 'active' ? 'inactive' : 'active';
        $discount->save();

        return $discount;
    }

    /**
     * Get all active discounts
     * 
     * @return Collection
     */
    public function getActiveDiscounts(): Collection
    {
        return Discount::active()->get();
    }
}