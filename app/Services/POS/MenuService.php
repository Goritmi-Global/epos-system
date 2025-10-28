<?php

namespace App\Services\POS;

use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\ProfileStep4;
use Illuminate\Http\Request;

class MenuService
{
    public function list(array $filters = [])
    {
        return MenuItem::query()
            ->when(
                $filters['q'] ?? null,
                fn ($q, $v) => $q->where('name', 'like', "%$v%")
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data, Request $request): MenuItem
    {
        // handle image upload
        if (isset($data['image']) && $data['image']) {
            $upload = UploadHelper::store($data['image'], 'uploads', 'public');
            $data['upload_id'] = $upload->id;
        }

        unset($data['image']);

        $menu = MenuItem::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description' => $data['description'] ?? null,
            'label_color' => $data['label_color'] ?? null,
            'upload_id' => $data['upload_id'] ?? null,
            'is_taxable' => ! empty($data['is_taxable']) ? 1 : 0,

        ]);

        // Nutrition
        $menu->nutrition()->create($data['nutrition'] ?? []);

        // Allergies + Tags - FIXED HERE
        if (! empty($data['allergies'])) {
            $syncData = [];

            foreach ($data['allergies'] as $index => $allergyId) {
                // Get the type from the parallel array
                $type = isset($data['allergy_types'][$index])
                    ? ((bool) $data['allergy_types'][$index] ? 1 : 0)
                    : 1; // default

                $syncData[$allergyId] = ['type' => $type];
            }

            $menu->allergies()->sync($syncData);
        }

        if (! empty($data['tags'])) {
            $menu->tags()->sync($data['tags']);
        }

        // Meals - Add this
        if (! empty($data['meals'])) {
            $menu->meals()->sync($data['meals']);
        }

        // Ingredients
        if (! empty($data['ingredients'])) {
            foreach ($data['ingredients'] as $ing) {
                $inventory = InventoryItem::find($ing['inventory_item_id']);

                $menu->ingredients()->create([
                    'inventory_item_id' => $ing['inventory_item_id'],
                    'product_name' => $inventory?->name ?? 'Unknown',
                    'quantity' => $ing['qty'],
                    'cost' => $ing['cost'] ?? 0,
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
        if (! empty($data['image'])) {
            $newUpload = UploadHelper::replace($menu->upload_id, $data['image'], 'uploads', 'public');
            $data['upload_id'] = $newUpload->id;
        }
        unset($data['image']);

        $taxRate = null;

        // Get tax info from onboarding step 4
        $profileStep4 = ProfileStep4::where('user_id', auth()->id())->first();

        if ($profileStep4 && $profileStep4->is_tax_registered == 1) {
            $taxRate = $profileStep4->tax_rate;
        }

        // -------------------------------
        // 2. Update base menu info
        // -------------------------------
        $menu->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? null,
            'label_color' => $data['label_color'] ?? null,
            'upload_id' => $data['upload_id'] ?? $menu->upload_id,
            'is_taxable' => ! empty($data['is_taxable']) ? 1 : 0,
            'tax_percentage' => ! empty($data['is_taxable']) ? $taxRate : null,
        ]);

        // -------------------------------
        // 3. Update Nutrition (totals only)
        // -------------------------------
        if (! empty($data['nutrition'])) {
            $menu->nutrition()->updateOrCreate(
                ['menu_item_id' => $menu->id],
                $data['nutrition']
            );
        }

        // -------------------------------
        // 4. Allergies & Tags (sync pivot)
        // -------------------------------
        if (! empty($data['allergies'])) {
            $syncData = [];

            foreach ($data['allergies'] as $index => $allergyId) {
                $type = isset($data['allergy_types'][$index])
                    ? ((int) $data['allergy_types'][$index] === 1 ? 1 : 0)
                    : 1; // default to Contain

                $syncData[$allergyId] = ['type' => $type];
            }

            $menu->allergies()->sync($syncData);
        } else {
            $menu->allergies()->detach();
        }

        $menu->tags()->sync($data['tags'] ?? []);
        $menu->meals()->sync($data['meals'] ?? []);

        // -------------------------------
        // 5. Ingredients (smart update)
        // -------------------------------
        if (! empty($data['ingredients'])) {
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
                        'menu_item_id' => $menu->id,
                        'inventory_item_id' => $ing['inventory_item_id'],
                    ],
                    [
                        'product_name' => $itemNames[$ing['inventory_item_id']] ?? 'Unknown',
                        'quantity' => $ing['qty'],
                        'cost' => $ing['cost'],
                    ]
                );
            }
        }

        // -------------------------------
        // 6. Return with relations loaded
        // -------------------------------
        return $menu->load(['nutrition', 'ingredients', 'allergies', 'tags']);
    }

    public function delete(MenuItem $menu): void
    {
        $menu->delete();
    }
}
