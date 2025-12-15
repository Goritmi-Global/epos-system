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
     * @param  array  $filters  - Optional filters (status, type, search)
     * @return Collection
     */
    public function getAllDiscounts(array $filters = [])
    {
        try {
            $query = Discount::query();

            // ✅ Search filter
            if (! empty($filters['q'])) {
                $query->where('name', 'like', "%{$filters['q']}%");
            }

            // ✅ Status filter
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // ✅ Type filter (using category parameter)
            if (! empty($filters['category'])) {
                $query->where('type', $filters['category']);
            }

            // ✅ Discount amount range filter (using price fields)
            if (isset($filters['price_min']) && $filters['price_min'] !== null && $filters['price_min'] !== '') {
                $query->where('discount_amount', '>=', (float) $filters['price_min']);
            }
            if (isset($filters['price_max']) && $filters['price_max'] !== null && $filters['price_max'] !== '') {
                $query->where('discount_amount', '<=', (float) $filters['price_max']);
            }

            if (! empty($filters['date_from'])) {
                $query->whereDate('start_date', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->whereDate('end_date', '<=', $filters['date_to']);
            }

            // ✅ Sorting
            if (! empty($filters['sort_by'])) {
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
                    default:
                        $query->orderBy('created_at', 'desc');
                        break;
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // ✅ Pagination
            $perPage = $filters['per_page'] ?? 10;

            return $query->paginate($perPage);

        } catch (\Exception $e) {
            \Log::error('Error fetching discounts: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get today's active discounts
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
     * @param  int  $perPage  - Number of records per page
     */
    public function getPaginatedDiscounts(int $perPage = 15): LengthAwarePaginator
    {
        return Discount::latest()->paginate($perPage);
    }

    /**
     * Create a new discount
     *
     * @param  array  $data  - Discount data
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
     * @param  int  $id  - Discount ID
     * @param  array  $data  - Updated data
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
     * @param  int  $id  - Discount ID
     */
    public function deleteDiscount(int $id): bool
    {
        $discount = Discount::findOrFail($id);

        return $discount->delete();
    }

    /**
     * Get a single discount by ID
     *
     * @param  int  $id  - Discount ID
     */
    public function getDiscountById(int $id): Discount
    {
        return Discount::findOrFail($id);
    }

    /**
     * Toggle discount status between active and inactive
     *
     * @param  int  $id  - Discount ID
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
     */
    public function getActiveDiscounts(): Collection
    {
        return Discount::active()->get();
    }
}
