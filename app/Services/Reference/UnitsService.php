<?php

namespace App\Services\Reference;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UnitsService
{
    /**
     * List units with optional filters:
     * - q: search text
     * - only_base: if truthy, return only base units (where base_unit_id IS NULL)
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Unit::query();

        if (! empty($filters['q'])) {
            $s = $filters['q'];
            $q->where('name', 'like', "%{$s}%");
        }

        if (! empty($filters['only_base'])) {
            $q->whereNull('base_unit_id');
        }

        return $q->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create one or many units.
     *
     * Accepts:
     * - ['units' => [['name' => 'kg'], ['name' => 'g']]]  // creates many base units
     * - ['name' => 'gram','base_unit_id' => 1, 'conversion_factor' => 0.001] // creates a single derived unit
     *
     * Returns created unit(s) (array or model)
     */
    public function create(array $data)
    {
        // If we got 'units' array => create multiple (assumed base units)
        if (! empty($data['units']) && is_array($data['units'])) {
            $created = [];
            DB::transaction(function () use ($data, &$created) {
                foreach ($data['units'] as $item) {
                    // $item is an array with at least 'name'. Allow optional base_unit_id and conversion_factor if provided.
                    $unitData = [
                        'name' => $item['name'],
                        'base_unit_id' => $item['base_unit_id'] ?? null,
                        'conversion_factor' => $item['conversion_factor'] ?? 1,
                    ];
                    $created[] = Unit::create($unitData);
                }
            });

            return $created;
        }

        // create single unit (derived or base)
        $unit = Unit::create([
            'name' => $data['name'],
            'base_unit_id' => $data['base_unit_id'] ?? null,
            'conversion_factor' => $data['conversion_factor'] ?? 1,
        ]);

        return $unit;
    }

    /**
     * Update an existing unit
     */
   public function update(Unit $unit, array $data): Unit
{
    $unit->name = $data['name'] ?? $unit->name;

    if (array_key_exists('base_unit_id', $data)) {
        $unit->base_unit_id = $data['base_unit_id'] ?: null;
    }

    if (array_key_exists('conversion_factor', $data)) {
        $unit->conversion_factor = $data['conversion_factor'] ?: 1;
    }

    $unit->save();

    return $unit;
}


    /**
     * Delete a unit
     */
    public function delete(Unit $unit): void
    {
        // Consider what to do with derived units — set base_unit_id null or cascade deletion.
        // Current migration uses onDelete('set null'), so deleting a base unit leaves derived units with NULL base_unit_id.
        $unit->delete();
    }
}
