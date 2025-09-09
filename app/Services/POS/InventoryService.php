<?php

namespace App\Services\POS;

use App\Models\InventoryItem;
use App\Models\InventoryItemNutrition;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function list(array $filters = [])
    {
        return InventoryItem::with([
                'user:id,name',
                'allergies:id,name',
                'tags:id,name',
                'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
            ])
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('name', 'like', "%{$v}%")
                       ->orWhere('sku', 'like', "%{$v}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->through(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'category'    => $item->category,
                    'subcategory' => $item->subcategory,
                    'minAlert'    => $item->minAlert,
                    'unit'        => $item->unit,
                    'supplier'    => $item->supplier,
                    'sku'         => $item->sku,
                    'description' => $item->description,

                    // Pivots
                    'allergies'   => $item->allergies->pluck('name')->values(),
                    'allergy_ids' => $item->allergies->pluck('id')->values(),
                    'tags'        => $item->tags->pluck('name')->values(),
                    'tag_ids'     => $item->tags->pluck('id')->values(),

                    // Nutrition (from hasOne table)
                    'nutrition'   => [
                        'calories' => (float) ($item->nutrition->calories ?? 0),
                        'protein'  => (float) ($item->nutrition->protein  ?? 0),
                        'fat'      => (float) ($item->nutrition->fat      ?? 0),
                        'carbs'    => (float) ($item->nutrition->carbs    ?? 0),
                    ],

                    'user'        => $item->user?->name,
                    'image_url'   => UploadHelper::url($item->upload_id),
                    'created_at'  => $item->created_at?->format('Y-m-d H:i'),
                ];
            });
    }

    public function create(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {
            // Upload image -> uploads table
            if (!empty($data['image'])) {
                $upload = UploadHelper::store($data['image'], 'uploads', 'public');
                $data['upload_id'] = $upload->id;
            }
            unset($data['image']);

            $data['user_id'] = auth()->id();

            // Extract pivot + nutrition payloads
            [$allergyIds, $tagIds] = $this->extractPivots($data);
            $nutritionPayload      = $this->extractNutrition($data);

            // Create item
            $item = InventoryItem::create($data);

            // Sync pivots
            $item->allergies()->sync($allergyIds);
            $item->tags()->sync($tagIds);

            // Upsert nutrition (hasOne)
            if (!empty($nutritionPayload)) {
                InventoryItemNutrition::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    $nutritionPayload
                );
            }

            return $item->load([
                'allergies:id,name',
                'tags:id,name',
                'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
                'user:id,name',
            ]);
        });
    }

    public function update(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data) {
            // Replace/upload image if new one provided
            if (!empty($data['image'])) {
                $newUpload = UploadHelper::replace($item->upload_id, $data['image'], 'uploads', 'public');
                $data['upload_id'] = $newUpload->id;
            }
            unset($data['image']);

            // Extract optional pivot + nutrition payloads
            $pivotsProvided       = $this->pivotsProvided($data);
            [$allergyIds, $tagIds] = $this->extractPivots($data);
            $nutritionPayload      = $this->extractNutrition($data);

            // Update main columns
            $item->update($data);

            // Sync pivots only if sent (partial update friendly)
            if ($pivotsProvided['allergies']) {
                $item->allergies()->sync($allergyIds);
            }
            if ($pivotsProvided['tags']) {
                $item->tags()->sync($tagIds);
            }

            // Upsert nutrition if sent
            if (!empty($nutritionPayload)) {
                InventoryItemNutrition::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    $nutritionPayload
                );
            }

            return $item->fresh()->load([
                'allergies:id,name',
                'tags:id,name',
                'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
                'user:id,name',
            ]);
        });
    }

    public function delete(InventoryItem $item): void
    {
        DB::transaction(function () use ($item) {
            // If FKs are set to cascade, no need to manually detach
            $item->delete();
        });
    }

    /** ------------------------- Helpers ------------------------- */

    private function extractPivots(array &$data): array
    {
        $allergyIds = $data['allergy_ids'] ?? [];
        $tagIds     = $data['tag_ids']     ?? [];

        unset($data['allergy_ids'], $data['tag_ids']);

        return [
            is_array($allergyIds) ? $allergyIds : [],
            is_array($tagIds)     ? $tagIds     : [],
        ];
    }

    private function pivotsProvided(array $data): array
    {
        return [
            'allergies' => array_key_exists('allergy_ids', $data),
            'tags'      => array_key_exists('tag_ids', $data),
        ];
    }

    /**
     * Accepts either:
     * - $data['nutrition'] = ['calories'=>.., 'protein'=>.., 'fat'=>.., 'carbs'=>..]
     * - or flattened keys: nutrition_calories, nutrition_protein, nutrition_fat, nutrition_carbs
     * Returns normalized payload and unsets consumed keys.
     */
    private function extractNutrition(array &$data): array
    {
        $payload = [];

        if (isset($data['nutrition']) && is_array($data['nutrition'])) {
            $n = $data['nutrition'];
            $payload = [
                'calories' => isset($n['calories']) ? (float) $n['calories'] : 0,
                'protein'  => isset($n['protein'])  ? (float) $n['protein']  : 0,
                'fat'      => isset($n['fat'])      ? (float) $n['fat']      : 0,
                'carbs'    => isset($n['carbs'])    ? (float) $n['carbs']    : 0,
            ];
            unset($data['nutrition']);
        } else {
            $hasAny = false;
            foreach (['calories','protein','fat','carbs'] as $k) {
                $key = "nutrition_{$k}";
                if (array_key_exists($key, $data)) {
                    $payload[$k] = (float) $data[$key];
                    unset($data[$key]);
                    $hasAny = true;
                }
            }
            if (!$hasAny) {
                $payload = [];
            } else {
                // Ensure all keys exist
                $payload = array_merge(['calories'=>0,'protein'=>0,'fat'=>0,'carbs'=>0], $payload);
            }
        }

        // Remove empties (so we don't upsert blank rows on update)
        $nonZero = array_filter($payload, fn($v) => $v !== null);
        return $nonZero;
    }
}
