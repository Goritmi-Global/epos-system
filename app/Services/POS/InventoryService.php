<?php

namespace App\Services\POS;

use App\Models\Inventory;
use App\Models\Allergy;
use App\Models\Tag;

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
            ->through(function ($item) {
                // ✅ Decode JSON safely only if string
                $allergyIds = is_string($item->allergies)
                    ? json_decode($item->allergies, true)
                    : ($item->allergies ?? []);

                $tagIds = is_string($item->tags)
                    ? json_decode($item->tags, true)
                    : ($item->tags ?? []);

                $nutrition = is_string($item->nutrition)
                    ? json_decode($item->nutrition, true)
                    : ($item->nutrition ?? []);

                // ✅ Fetch related names
                $allergyNames = !empty($allergyIds)
                    ? Allergy::whereIn('id', $allergyIds)->pluck('name')->toArray()
                    : [];

                $tagNames = !empty($tagIds)
                    ? Tag::whereIn('id', $tagIds)->pluck('name')->toArray()
                    : [];

                // ✅ Return clean object
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "category" => $item->category,
                    "subcategory" => $item->subcategory,
                    "minAlert" => $item->minAlert,
                    "unit" => $item->unit,
                    "supplier" => $item->supplier,
                    "sku" => $item->sku,
                    "description" => $item->description,
                    "nutrition" => $nutrition,
                    "allergies" => $allergyNames,
                    "tags" => $tagNames,
                    "user" => $item->user?->name,
                    "created_at" => $item->created_at,
                    "updated_at" => $item->updated_at,
                ];
            });
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
