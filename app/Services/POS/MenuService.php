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
            'image'         => $data['image'] ?? null,
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
        $menu->update($data);
        return $menu;
    }

    public function delete(MenuItem $menu): void
    {
        $menu->delete();
    }
}
