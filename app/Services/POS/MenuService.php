<?php

namespace App\Services\POS;

use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\MenuItemAddonGroup;
use App\Models\MenuVariant;
use App\Models\MenuVariantIngredient;
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
        // Handle image upload
        if (isset($data['image']) && $data['image']) {
            $upload = UploadHelper::store($data['image'], 'uploads', 'public');
            $data['upload_id'] = $upload->id;
        }

        unset($data['image']);

        // ✅ FIX: Check for variant_metadata instead of variant_ingredients
        $isVariantMenu = ! empty($data['variant_metadata']) && is_array($data['variant_metadata']);

        $resaleData = [
            'is_saleable' => $data['is_saleable'] ?? false,
            'resale_type' => $data['resale_type'] ?? null,
            'resale_value' => $data['resale_value'] ?? null,
        ];

        // Create menu item
        $menu = MenuItem::create([
            'name' => $data['name'],
            'price' => $isVariantMenu ? 0 : ($data['price'] ?? 0), // base price not needed for variants
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description' => $data['description'] ?? null,
            'label_color' => $data['label_color'] ?? null,
            'upload_id' => $data['upload_id'] ?? null,
            'is_taxable' => ! empty($data['is_taxable']) ? 1 : 0,
            ...$resaleData,
        ]);

        // Nutrition
        $menu->nutrition()->create($data['nutrition'] ?? []);

        // Allergies
        if (! empty($data['allergies'])) {
            $syncData = [];
            foreach ($data['allergies'] as $index => $allergyId) {
                $type = isset($data['allergy_types'][$index])
                    ? ((bool) $data['allergy_types'][$index] ? 1 : 0)
                    : 1;
                $syncData[$allergyId] = ['type' => $type];
            }
            $menu->allergies()->sync($syncData);
        }

        // Tags
        if (! empty($data['tags'])) {
            $menu->tags()->sync($data['tags']);
        }

        // Meals
        if (! empty($data['meals'])) {
            $menu->meals()->sync($data['meals']);
        }

        // Handle ingredients based on menu type
        if ($isVariantMenu) {
            // ✅ Process variant menu
            foreach ($data['variant_metadata'] as $index => $variantData) {
                // Create variant record
                $variant = MenuVariant::create([
                    'menu_item_id' => $menu->id,
                    'name' => $variantData['name'],
                    'price' => $variantData['price'] ?? 0,
                    'is_saleable' => isset($variantData['is_saleable']) ? (bool) $variantData['is_saleable'] : false,
                    'resale_type' => $variantData['resale_type'] ?? null,
                    'resale_value' => $variantData['resale_value'] ?? null,
                ]);

                // Save ingredients for this variant
                if (! empty($data['variant_ingredients'][$index])) {
                    foreach ($data['variant_ingredients'][$index] as $ing) {
                        $inventory = InventoryItem::find($ing['inventory_item_id']);

                        MenuVariantIngredient::create([
                            'menu_item_id' => $menu->id,
                            'variant_id' => $variant->id,
                            'inventory_item_id' => $ing['inventory_item_id'],
                            'product_name' => $inventory?->name ?? 'Unknown',
                            'quantity' => $ing['qty'],
                            'cost' => $ing['cost'] ?? 0,
                        ]);
                    }
                }
            }
        } else {
            // Store simple menu ingredients
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
        }

        // Addon Group
        if (! empty($data['addon_group_id'])) {
            MenuItemAddonGroup::create([
                'menu_item_id' => $menu->id,
                'addon_group_id' => $data['addon_group_id'],
            ]);
        }

        return $menu->load([
            'nutrition',
            'ingredients',
            'variants.ingredients',
            'allergies',
            'tags',
            'meals',
            'addonGroupRelations',
        ]);
    }

    public function update(MenuItem $menu, array $data, Request $request): MenuItem
    {
        // Handle image upload
        if (isset($data['image']) && $data['image']) {
            // Delete old image if exists
            if ($menu->upload_id) {
                $oldUpload = Upload::find($menu->upload_id);
                if ($oldUpload) {
                    UploadHelper::delete($oldUpload);
                }
            }

            $upload = UploadHelper::store($data['image'], 'uploads', 'public');
            $data['upload_id'] = $upload->id;
        }

        unset($data['image']);

        // ✅ Check for variant_metadata to determine menu type
        $isVariantMenu = ! empty($data['variant_metadata']) && is_array($data['variant_metadata']);

        // Update menu item basic info
        $menu->update([
            'name' => $data['name'],
            'price' => $isVariantMenu ? 0 : ($data['price'] ?? 0),
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description' => $data['description'] ?? null,
            'label_color' => $data['label_color'] ?? null,
            'upload_id' => $data['upload_id'] ?? $menu->upload_id,
            'is_taxable' => ! empty($data['is_taxable']) ? 1 : 0,
            'is_saleable' => $data['is_saleable'] ?? false,
            'resale_type' => $data['resale_type'] ?? null,
            'resale_value' => $data['resale_value'] ?? null,
        ]);

        // Update Nutrition
        $menu->nutrition()->updateOrCreate(
            ['menu_item_id' => $menu->id],
            $data['nutrition'] ?? []
        );

        // Update Allergies
        if (isset($data['allergies'])) {
            $syncData = [];
            foreach ($data['allergies'] as $index => $allergyId) {
                $type = isset($data['allergy_types'][$index])
                    ? ((bool) $data['allergy_types'][$index] ? 1 : 0)
                    : 1;
                $syncData[$allergyId] = ['type' => $type];
            }
            $menu->allergies()->sync($syncData);
        }

        // Update Tags
        if (isset($data['tags'])) {
            $menu->tags()->sync($data['tags']);
        }

        // Update Meals
        if (isset($data['meals'])) {
            $menu->meals()->sync($data['meals']);
        }

        // ✅ Handle ingredients based on menu type
        if ($isVariantMenu) {
            // Delete old simple ingredients if switching from simple to variant
            $menu->ingredients()->delete();

            // Get existing variant IDs
            $existingVariantIds = $menu->variants()->pluck('id')->toArray();
            $submittedVariantIds = [];

            foreach ($data['variant_metadata'] as $index => $variantData) {
                $variantId = $variantData['id'] ?? null;

                if ($variantId && in_array($variantId, $existingVariantIds)) {
                    // Update existing variant
                    $variant = MenuVariant::find($variantId);
                    $variant->update([
                        'name' => $variantData['name'],
                        'price' => $variantData['price'] ?? 0,
                         'is_saleable' => isset($variantData['is_saleable']) ? (bool) $variantData['is_saleable'] : false,
                        'resale_type' => $variantData['resale_type'] ?? null,
                        'resale_value' => $variantData['resale_value'] ?? null,
                    ]);
                    $submittedVariantIds[] = $variantId;
                } else {
                    // Create new variant
                    $variant = MenuVariant::create([
                        'menu_item_id' => $menu->id,
                        'name' => $variantData['name'],
                        'price' => $variantData['price'] ?? 0,
                        'is_saleable' => isset($variantData['is_saleable']) ? (bool) $variantData['is_saleable'] : false,
                        'resale_type' => $variantData['resale_type'] ?? null,
                        'resale_value' => $variantData['resale_value'] ?? null,
                    ]);
                    $submittedVariantIds[] = $variant->id;
                }

                // Delete old ingredients for this variant
                MenuVariantIngredient::where('menu_item_id', $menu->id)
                    ->where('variant_id', $variant->id)
                    ->delete();

                // Add new ingredients
                if (! empty($data['variant_ingredients'][$index])) {
                    foreach ($data['variant_ingredients'][$index] as $ing) {
                        $inventory = InventoryItem::find($ing['inventory_item_id']);

                        MenuVariantIngredient::create([
                            'menu_item_id' => $menu->id,
                            'variant_id' => $variant->id,
                            'inventory_item_id' => $ing['inventory_item_id'],
                            'product_name' => $inventory?->name ?? 'Unknown',
                            'quantity' => $ing['qty'],
                            'cost' => $ing['cost'] ?? 0,
                        ]);
                    }
                }
            }

            // Delete variants that were removed
            MenuVariant::where('menu_item_id', $menu->id)
                ->whereNotIn('id', $submittedVariantIds)
                ->delete();

        } else {
            // Delete old variant data if switching from variant to simple
            $menu->variants()->delete();
            MenuVariantIngredient::where('menu_item_id', $menu->id)->delete();

            // Delete old simple ingredients
            $menu->ingredients()->delete();

            // Add new simple ingredients
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
        }

        // Update Addon Group
        MenuItemAddonGroup::where('menu_item_id', $menu->id)->delete();
        if (! empty($data['addon_group_id'])) {
            MenuItemAddonGroup::create([
                'menu_item_id' => $menu->id,
                'addon_group_id' => $data['addon_group_id'],
            ]);
        }

        return $menu->load([
            'nutrition',
            'ingredients',
            'variants.ingredients',
            'allergies',
            'tags',
            'meals',
            'addonGroupRelations',
        ]);
    }

    public function delete(MenuItem $menu): void
    {
        $menu->delete();
    }
}
