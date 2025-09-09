<?php

namespace App\Services\POS;

use App\Models\InventoryItem;
use App\Models\InventoryItemNutrition;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /** ----------------------------------------------------------------
     *  Read / List
     *  ---------------------------------------------------------------- */
    public function list(array $filters = [])
    {
        return InventoryItem::query()
            ->with([
                'user:id,name',
                'supplier:id,name',
                'unit:id,name',
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
            ->through(function (InventoryItem $item) {
                return [
                    'id'            => $item->id,
                    'name'          => $item->name,
                    'sku'           => $item->sku,
                    'description'   => $item->description,

                    // keep if you use these columns on inventory_items
                    'category'      => $item->category ?? null,
                    'subcategory'   => $item->subcategory ?? null,
                    'minAlert'      => $item->minAlert ?? null,

                    // supplier / unit stored on inventory_items
                    'supplier_id'   => $item->supplier_id,
                    'supplier_name' => $item->supplier?->name,
                    'unit_id'       => $item->unit_id,
                    'unit_name'     => $item->unit?->name,

                    // Pivots
                    'allergies'     => $item->allergies->pluck('name')->values(),
                    'allergy_ids'   => $item->allergies->pluck('id')->values(),
                    'tags'          => $item->tags->pluck('name')->values(),
                    'tag_ids'       => $item->tags->pluck('id')->values(),

                    // Nutrition
                    'nutrition'     => [
                        'calories' => (float) ($item->nutrition->calories ?? 0),
                        'protein'  => (float) ($item->nutrition->protein  ?? 0),
                        'fat'      => (float) ($item->nutrition->fat      ?? 0),
                        'carbs'    => (float) ($item->nutrition->carbs    ?? 0),
                    ],

                    'user'          => $item->user?->name,
                    'image_url'     => UploadHelper::url($item->upload_id),
                    'created_at'    => optional($item->created_at)->format('Y-m-d H:i'),
                ];
            });
    }

    /** ----------------------------------------------------------------
     *  Create
     *  ---------------------------------------------------------------- */
    public function create(array $data): InventoryItem
    {
        // dd($data);
        return DB::transaction(function () use ($data) {
            // Image -> uploads table
            if (!empty($data['image'])) {
                $upload = UploadHelper::store($data['image'], 'uploads', 'public');
                $data['upload_id'] = $upload->id;
            }
            unset($data['image']);

            // who created it
            $data['user_id'] = auth()->id();

            // ensure the FK fields exist as integers on inventory_items
            $data['supplier_id'] = isset($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $data['unit_id']     = isset($data['unit_id'])     ? (int) $data['unit_id']     : null;

            // Extract pivots & nutrition BEFORE create (they’re removed from $data)
            [$allergyIds, $tagIds] = $this->extractPivots($data);
            $nutritionPayload       = $this->extractNutrition($data);

            // Create base row
            $item = InventoryItem::create($data);

            // Sync pivots (detach/attach exact set)
            $item->allergies()->sync($allergyIds);
            $item->tags()->sync($tagIds);

            // Upsert nutrition if any provided
            if ($nutritionPayload !== []) {
                InventoryItemNutrition::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    $nutritionPayload
                );
            }

            return $item->load([
                'user:id,name',
                'supplier:id,name',
                'unit:id,name',
                'allergies:id,name',
                'tags:id,name',
                'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
            ]);
        });
    }

    /** ----------------------------------------------------------------
     *  Update
     *  ---------------------------------------------------------------- */
    public function update(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data) {
            // replace/upload image if new one provided
            if (!empty($data['image'])) {
                $newUpload = UploadHelper::replace($item->upload_id, $data['image'], 'uploads', 'public');
                $data['upload_id'] = $newUpload->id;
            }
            unset($data['image']);

            // FKs (optional on update)
            if (array_key_exists('supplier_id', $data)) {
                $data['supplier_id'] = $data['supplier_id'] !== null ? (int) $data['supplier_id'] : null;
            }
            if (array_key_exists('unit_id', $data)) {
                $data['unit_id'] = $data['unit_id'] !== null ? (int) $data['unit_id'] : null;
            }

            // detect if pivots were sent (partial update friendly)
            $pivotsProvided         = $this->pivotsProvided($data);
            [$allergyIds, $tagIds]  = $this->extractPivots($data);
            $nutritionPayload       = $this->extractNutrition($data);

            // update scalar columns
            $item->update($data);

            // sync pivots only when provided in request
            if ($pivotsProvided['allergies']) {
                $item->allergies()->sync($allergyIds);
            }
            if ($pivotsProvided['tags']) {
                $item->tags()->sync($tagIds);
            }

            // upsert nutrition only if provided
            if ($nutritionPayload !== []) {
                InventoryItemNutrition::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    $nutritionPayload
                );
            }

            return $item->fresh()->load([
                'user:id,name',
                'supplier:id,name',
                'unit:id,name',
                'allergies:id,name',
                'tags:id,name',
                'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
            ]);
        });
    }

    /** ----------------------------------------------------------------
     *  Delete
     *  ---------------------------------------------------------------- */
    public function delete(InventoryItem $item): void
    {
        DB::transaction(function () use ($item) {
            // If FKs are set to cascade, no need to manually detach
            $item->delete();
        });
    }

    /** ----------------------------------------------------------------
     *  Helpers
     *  ---------------------------------------------------------------- */

    /**
     * Extract allergy/tag IDs from $data. Accepts:
     * - 'allergies' or 'allergy_ids' (array or JSON), and
     * - 'tags' or 'tag_ids' (array or JSON).
     * Removes these keys from $data.
     */
    private function extractPivots(array &$data): array
    {
        $allergyIds = $data['allergies'] ?? $data['allergy_ids'] ?? [];
        $tagIds     = $data['tags']      ?? $data['tag_ids']      ?? [];

        if (is_string($allergyIds)) $allergyIds = json_decode($allergyIds, true) ?: [];
        if (is_string($tagIds))     $tagIds     = json_decode($tagIds, true)     ?: [];

        $allergyIds = collect($allergyIds)->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
        $tagIds     = collect($tagIds)->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();

        unset($data['allergies'], $data['allergy_ids'], $data['tags'], $data['tag_ids']);

        return [$allergyIds, $tagIds];
    }

    /**
     * For partial updates: did the request include pivot arrays?
     */
    private function pivotsProvided(array $data): array
    {
        return [
            'allergies' => array_key_exists('allergies', $data) || array_key_exists('allergy_ids', $data),
            'tags'      => array_key_exists('tags', $data)      || array_key_exists('tag_ids', $data),
        ];
    }

    /**
     * Accepts either:
     * - $data['nutrition'] = ['calories'=>.., 'protein'=>.., 'fat'=>.., 'carbs'=>..]
     * - or flattened keys: nutrition_calories, nutrition_protein, nutrition_fat, nutrition_carbs
     * Removes consumed keys and returns a normalized payload,
     * or [] if nothing nutrition-related was provided.
     */
    private function extractNutrition(array &$data): array
    {
        $payload = [];

        if (isset($data['nutrition'])) {
            $n = is_string($data['nutrition']) ? json_decode($data['nutrition'], true) : $data['nutrition'];
            $n = is_array($n) ? $n : [];

            $payload = [
                'calories' => isset($n['calories']) ? (float) $n['calories'] : 0,
                'protein'  => isset($n['protein'])  ? (float) $n['protein']  : 0,
                'fat'      => isset($n['fat'])      ? (float) $n['fat']      : 0,
                'carbs'    => isset($n['carbs'])    ? (float) $n['carbs']    : 0,
            ];
            unset($data['nutrition']);
        } else {
            $touched = false;
            foreach (['calories','protein','fat','carbs'] as $k) {
                $key = "nutrition_{$k}";
                if (array_key_exists($key, $data)) {
                    $payload[$k] = (float) $data[$key];
                    unset($data[$key]);
                    $touched = true;
                }
            }
            if ($touched) {
                $payload = array_merge(['calories'=>0,'protein'=>0,'fat'=>0,'carbs'=>0], $payload);
            }
        }

        // If nothing nutrition-related was provided, return empty array.
        return $payload ?: [];
    }
}
