<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PromoService
{

    public function getAllPromos(array $filters = []): Collection
    {
        $query = Promo::query()->latest();


        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->get();
    }

    /**
     * Get today's active promos
     */
    public function getTodayPromos(): Collection
    {
        return Promo::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
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
            'discount' => $data['discount'],
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
