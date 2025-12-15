<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PromoService
{

   public function getAllPromos(array $filters = [])
{
    try {
        $query = Promo::query();

        // ✅ Search filter
        if (!empty($filters['q'])) {
            $query->where('name', 'like', "%{$filters['q']}%");
        }

        // ✅ Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // ✅ Type filter (using category parameter)
        if (!empty($filters['category'])) {
            $query->where('type', $filters['category']);
        }

        // ✅ Discount amount range filter (using price fields)
        if (isset($filters['price_min']) && $filters['price_min'] !== null && $filters['price_min'] !== '') {
            $query->where('discount_amount', '>=', (float)$filters['price_min']);
        }
        if (isset($filters['price_max']) && $filters['price_max'] !== null && $filters['price_max'] !== '') {
            $query->where('discount_amount', '<=', (float)$filters['price_max']);
        }

        // ✅ Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        // ✅ Sorting
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
        \Log::error('Error fetching promos: ' . $e->getMessage());
        throw $e;
    }
}

    /**
     * Get today's active promos
     */
    public function getTodayPromos(): Collection
    {
        return Promo::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->latest()
            ->get();
    }


    /**
     * Get paginated promos
     */
    public function getPaginatedPromos(int $perPage = 15): LengthAwarePaginator
    {
        return Promo::latest()->paginate($perPage);
    }

    /**
     * Create a new promo
     */
    public function createPromo(array $data): Promo
    {
        return Promo::create([
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
     * Update an existing promo
     */
    public function updatePromo(int $id, array $data): Promo
    {
        $promo = Promo::findOrFail($id);

        $promo->update([
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

        return $promo->fresh();
    }

    /**
     * Delete a promo
     */
    public function deletePromo(int $id): bool
    {
        $promo = Promo::findOrFail($id);
        return $promo->delete();
    }

    /**
     * Get a single promo by ID
     */
    public function getPromoById(int $id): Promo
    {
        return Promo::findOrFail($id);
    }

    /**
     * Toggle promo status
     */
    public function toggleStatus(int $id): Promo
    {
        $promo = Promo::findOrFail($id);
        $promo->status = $promo->status === 'active' ? 'inactive' : 'active';
        $promo->save();

        return $promo;
    }

    /**
     * Get active promos
     */
    public function getActivePromos(): Collection
    {
        return Promo::active()->get();
    }
}
