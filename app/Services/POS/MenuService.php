<?php

namespace App\Services\POS;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use App\Helpers\UploadHelper;

class MenuService
{
    public function list(array $filters = [])
    {
        return MenuItem::query()
            ->when(
                $filters['q'] ?? null,
                fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data, Request $request): MenuItem
    {
        // handle image upload
        if (isset($data['image']) && $data['image']) {
            $upload      = UploadHelper::store($data['image'], 'uploads', 'public');
            $data['upload_id'] = $upload->id;
        }

        unset($data['image']);

        $menu = MenuItem::create([
            'name'          => $data['name'],
            'price'         => $data['price'],
            'category_id'   => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description'   => $data['description'] ?? null,
            'upload_id'      => $data['upload_id'] ?? null,
        ]);

        // Nutrition
        $menu->nutrition()->create($data['nutrition'] ?? []);

        // Allergies + Tags
        if (!empty($data['allergies'])) {
            $menu->allergies()->sync($data['allergies']);
        }
        if (!empty($data['tags'])) {
            $menu->tags()->sync($data['tags']);
        }

        // Ingredients
        if (!empty($data['ingredients'])) {
            foreach ($data['ingredients'] as $ing) {
                $inventory = InventoryItem::find($ing['inventory_item_id']);

                $menu->ingredients()->create([
                    'inventory_item_id' => $ing['inventory_item_id'],
                    'product_name'      => $inventory?->name ?? 'Unknown',
                    'quantity'          => $ing['qty'],
                    'cost'              => $ing['cost'] ?? 0,
                ]);
            }
        }

        return $menu;
    }



    public function update(MenuItem $menu, array $data): MenuItem
    {
        // -------------------------------
        // 1. Replace/upload image if provided
        // -------------------------------
        if (!empty($data['image'])) {
            $newUpload = UploadHelper::replace($menu->upload_id, $data['image'], 'uploads', 'public');
            $data['upload_id'] = $newUpload->id;
        }
        unset($data['image']); 

        // -------------------------------
        // 1. Update base menu info
        // -------------------------------
        $menu->update([
            'name'        => $data['name'],
            'price'       => $data['price'],
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? null,
            'upload_id'   => $data['upload_id'] ?? $menu->upload_id,
        ]);

        // -------------------------------
        // 2. Update Nutrition (totals only)
        // -------------------------------
        if (!empty($data['nutrition'])) {
            $menu->nutrition()->updateOrCreate(
                ['menu_item_id' => $menu->id],
                $data['nutrition']
            );
        }

        // -------------------------------
        // 3. Allergies & Tags (sync pivot)
        // -------------------------------
        $menu->allergies()->sync($data['allergies'] ?? []);
        $menu->tags()->sync($data['tags'] ?? []);

        // -------------------------------
        // 4. Ingredients (smart update)
        // -------------------------------
        $ingredientIds = collect($data['ingredients'])->pluck('inventory_item_id');

        // Remove old ones not in request
        $menu->ingredients()
            ->whereNotIn('inventory_item_id', $ingredientIds)
            ->delete();

        // Preload names once
        $itemNames = InventoryItem::whereIn('id', $ingredientIds)
            ->pluck('name', 'id');

        foreach ($data['ingredients'] as $ing) {
            $menu->ingredients()->updateOrCreate(
                [
                    'menu_item_id'       => $menu->id,
                    'inventory_item_id'  => $ing['inventory_item_id'],
                ],
                [
                    'product_name' => $itemNames[$ing['inventory_item_id']] ?? 'Unknown',
                    'quantity'     => $ing['qty'],
                    'cost'         => $ing['cost'],
                ]
            );
        }


        // -------------------------------
        // 5. Return with relations loaded
        // -------------------------------
        return $menu->load(['nutrition', 'ingredients', 'allergies', 'tags']);
    }



    public function delete(MenuItem $menu): void
    {
        $menu->delete();
    }
}
