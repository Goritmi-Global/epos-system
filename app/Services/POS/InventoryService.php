<?php

namespace App\Services\POS;

use App\Models\Inventory;

class InventoryService
{
    public function list(array $filters = [])
    {
        return Inventory::query()
            ->when($filters['q'] ?? null, fn($q, $v) =>
                $q->where(function ($qq) use ($v) {
                    $qq->where('name', 'like', "%$v%")
                       ->orWhere('sku', 'like', "%$v%");
                })
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update($data);
        return $inventory;
    }

    public function delete(Inventory $inventory): void
    {
        $inventory->delete();
    }
}
