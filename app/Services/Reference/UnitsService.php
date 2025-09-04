<?php

namespace App\Services\Reference;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitsService
{
    /**
     * Get list of units with optional search filter
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Unit::query();

        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where('name', 'like', "%{$s}%");
        }

        return $q->latest()->paginate(15);
    }

    
    /**
     * Create a new unit
     */
    public function create(array $data): Unit
    {
        $unit = new Unit();
        $unit->name = $data['name'];
        $unit->save();

        return $unit;
    }

    /**
     * Update an existing unit
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->name = $data['name'];
        $unit->save();

        return $unit;
    }

    /**
     * Delete a unit
     */
    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
