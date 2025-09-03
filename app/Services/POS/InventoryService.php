<?php

namespace App\Services\POS;

use App\Models\Inventory;

class InventoryService
{
    public function list(array $filters = [])
    {
        return Inventory::with('user')
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('name', 'like', "%$v%")
                        ->orWhere('sku', 'like', "%$v%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }


    public function create(array $data): Inventory
    {
        // Handle image upload
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('inventories', 'public');
        }
        $data['user_id'] = auth()->id();
        return Inventory::create($data);
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        // Handle new image if uploaded
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('inventories', 'public');
        }
        $inventory->update($data);
        return $inventory;
    }


    public function delete(Inventory $inventory): void
    {
        $inventory->delete();
    }
}
