<?php

namespace App\Services\POS;

use App\Models\InventoryItem;
use App\Models\InventoryItemNutrition;
use App\Helpers\UploadHelper;
use App\Models\StockEntry;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /** ----------------------------------------------------------------
     *  Read / List
     *  ---------------------------------------------------------------- */
    public function list(array $filters = [])
{
   $query = InventoryItem::query()
        ->with([
            'category', // ✅ Make sure category is loaded
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
                    ->orWhere('sku', 'like', "%{$v}%")
                    // ✅ ADD CATEGORY SEARCH
                    ->orWhereHas('category', function ($query) use ($v) {
                        $query->where('name', 'like', "%{$v}%");
                    })
                    // ✅ ADD UNIT SEARCH (optional)
                    ->orWhereHas('unit', function ($query) use ($v) {
                        $query->where('name', 'like', "%{$v}%");
                    })
                    // ✅ ADD SUPPLIER SEARCH (optional)
                    ->orWhereHas('supplier', function ($query) use ($v) {
                        $query->where('name', 'like', "%{$v}%");
                    });
            });
        })
        ->orderByDesc('id');

    // ✅ Check if there's a search query
    $searchQuery = trim($filters['q'] ?? '');
    $hasSearch = !empty($searchQuery);
    
    \Log::info('Inventory List Debug', [
        'raw_q' => $filters['q'] ?? null,
        'trimmed_q' => $searchQuery,
        'hasSearch' => $hasSearch,
        'per_page' => $filters['per_page'] ?? 200
    ]);
    
    if ($hasSearch) {
        // ✅ When searching: Get ALL matching results (no pagination)
        $items = $query->get();
        $total = $items->count();
        
        \Log::info('Search Mode', [
            'total_found' => $total
        ]);
        
        // Create a single-page paginator
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $total > 0 ? $total : 1, // per_page = total
            1, // always page 1
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );
    } else {
        // ✅ No search: Normal pagination
        \Log::info('Pagination Mode');
        $paginator = $query->paginate($filters['per_page'] ?? 200);
    }

    // Get all product IDs from current page/results
    $productIds = $paginator->pluck('id')->toArray();

    // Calculate stock for ALL products in ONE bulk operation
    $stockData = app(StockEntryService::class)->bulkTotalStock($productIds);

    return $paginator->through(function (InventoryItem $item) use ($stockData) {
        $stock = $stockData[$item->id] ?? [
            'available' => 0,
            'stockValue' => '0.00',
            'minAlert' => 0,
            'expiry_date' => null,
            'status' => null,
        ];

        return [
            'id'            => $item->id,
            'name'          => $item->name,
            'sku'           => $item->sku,
            'description'   => $item->description,
            'category'      => $item->category ?? null,
            'subcategory'   => $item->subcategory ?? null,
            'minAlert'      => $stock['minAlert'],
            'supplier_id'   => $item->supplier_id,
            'supplier_name' => $item->supplier?->name,
            'unit_id'       => $item->unit_id,
            'unit_name'     => $item->unit?->name,
            'stock'         => $item->stock,

            // Stock data from bulk calculation
            'availableStock' => $stock['available'],
            'stockValue'     => $stock['stockValue'],
            'expiry_date'    => $stock['expiry_date'],
            'status'         => $stock['status'],

            'allergies'     => $item->allergies->pluck('name')->values(),
            'allergy_ids'   => $item->allergies->pluck('id')->values(),
            'tags'          => $item->tags->pluck('name')->values(),
            'tag_ids'       => $item->tags->pluck('id')->values(),
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

            // FKs
            $data['supplier_id'] = isset($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $data['unit_id']     = isset($data['unit_id'])     ? (int) $data['unit_id']     : null;

            // Resolve category_id from subcategory_id or category_id
            $data['category_id'] = $this->resolveCategoryId($data);

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

            //  Only resolve when either key was sent (partial update-safe)
            if (array_key_exists('subcategory_id', $data) || array_key_exists('category_id', $data)) {
                $data['category_id'] = $this->resolveCategoryId($data);
            } else {
                // ensure we don't pass unknown column
                unset($data['subcategory_id']);
            }

            // detect pivots + nutrition...
            $pivotsProvided        = $this->pivotsProvided($data);
            [$allergyIds, $tagIds] = $this->extractPivots($data);
            $nutritionPayload      = $this->extractNutrition($data);

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

        $allergyIds = collect($allergyIds)->map(fn($v) => (int) $v)->filter()->unique()->values()->all();
        $tagIds     = collect($tagIds)->map(fn($v) => (int) $v)->filter()->unique()->values()->all();

        unset($data['allergies'], $data['allergy_ids'], $data['tags'], $data['tag_ids']);

        return [$allergyIds, $tagIds];
    }
    private function resolveCategoryId(array &$data): ?int
    {
        $sub = $data['subcategory_id'] ?? null;
        $cat = $data['category_id']    ?? null;

        // choose subcategory first, else category
        $resolved = null;
        if ($sub !== null && $sub !== '') {
            $resolved = (int) $sub;
        } elseif ($cat !== null && $cat !== '') {
            $resolved = (int) $cat;
        }

        // remove request-only keys so mass-assign doesn’t choke
        unset($data['subcategory_id']);

        // keep resolved category_id in payload
        return $resolved ?: null;
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
            foreach (['calories', 'protein', 'fat', 'carbs'] as $k) {
                $key = "nutrition_{$k}";
                if (array_key_exists($key, $data)) {
                    $payload[$k] = (float) $data[$key];
                    unset($data[$key]);
                    $touched = true;
                }
            }
            if ($touched) {
                $payload = array_merge(['calories' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0], $payload);
            }
        }

        // If nothing nutrition-related was provided, return empty array.
        return $payload ?: [];
    }


    // Show data
    public function show(InventoryItem $item): array
    {
        $item->load([
            'user:id,name',
            'supplier:id,name',
            'unit:id,name',
            'category:id,name,parent_id',
            'allergies:id,name',
            'tags:id,name',
            'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
        ]);

        return $this->formatItem($item);
    }

    protected function formatItem(InventoryItem $item): array
    {
        return [
            'id'            => $item->id,
            'name'          => $item->name,
            'sku'           => $item->sku,
            'description'   => $item->description,
            'minAlert'      => $item->minAlert,

            // FKs
            'supplier_id'   => $item->supplier_id,
            'supplier_name' => $item->supplier?->name,
            'unit_id'       => $item->unit_id,
            'unit_name'     => $item->unit?->name,
            'category_id'   => $item->category_id,
            'category_name' => $item->category?->name,

            // Pivots
            'allergies'     => $item->allergies->pluck('name')->values(),
            'allergy_ids'   => $item->allergies->pluck('id')->values(),
            'tags'          => $item->tags->pluck('name')->values(),
            'tag_ids'       => $item->tags->pluck('id')->values(),

            // Nutrition (hasOne table)
            'nutrition'     => [
                'calories' => (float) ($item->nutrition->calories ?? 0),
                'protein'  => (float) ($item->nutrition->protein  ?? 0),
                'fat'      => (float) ($item->nutrition->fat      ?? 0),
                'carbs'    => (float) ($item->nutrition->carbs    ?? 0),
            ],

            // Meta
            'user'          => $item->user?->name,
            'upload_id'     => $item->upload_id,
            'image_url'     => UploadHelper::url($item->upload_id) ?? asset('assets/img/default.png'),
            'created_at'    => optional($item->created_at)->format('Y-m-d H:i'),
            'updated_at'    => optional($item->updated_at)->format('Y-m-d H:i'),
        ];
    }


    // Edit data
    public function editPayload(InventoryItem $item): array
    {
        $item->load([
            'user:id,name',
            'supplier:id,name',
            'unit:id,name',
            // load category and its parent so we can derive both ids
            'category:id,name,parent_id',
            'category.parent:id,name',
            'allergies:id',
            'tags:id',
            'nutrition:id,inventory_item_id,calories,protein,fat,carbs',
        ]);

        $cat = $item->category; // may be null, a parent, or a subcategory

        // If $cat has a parent_id => $cat IS a subcategory.
        $resolvedCategoryId   = $cat?->parent_id ? (int) $cat->parent_id : ($cat?->id ? (int) $cat->id : null);
        $resolvedSubcategoryId = $cat?->parent_id ? (int) $cat->id : null;

        $categoryName         = $cat?->parent_id ? ($cat->parent?->name) : ($cat?->name);
        $subcategoryName      = $cat?->parent_id ? $cat->name : null;

        $imageUrl = UploadHelper::url($item->upload_id) ?? asset('assets/img/default.png');

        return [
            'id'          => $item->id,
            'name'        => $item->name,
            'sku'         => $item->sku,
            'description' => $item->description,
            'minAlert'    => $item->minAlert,

            // >>> send BOTH ids for the form <<<
            'category_id'    => $resolvedCategoryId,
            'subcategory_id' => $resolvedSubcategoryId,

            // optional display names (useful if you show them)
            'category_name'    => $categoryName,
            'subcategory_name' => $subcategoryName,

            'supplier_id'   => $item->supplier_id,
            'unit_id'       => $item->unit_id,
            'supplier_name' => $item->supplier?->name,
            'unit_name'     => $item->unit?->name,

            'allergy_ids' => $item->allergies->pluck('id')->values(),
            'tag_ids'     => $item->tags->pluck('id')->values(),

            'nutrition' => [
                'calories' => (float) ($item->nutrition->calories ?? 0),
                'protein'  => (float) ($item->nutrition->protein  ?? 0),
                'fat'      => (float) ($item->nutrition->fat      ?? 0),
                'carbs'    => (float) ($item->nutrition->carbs    ?? 0),
            ],

            'upload_id'  => $item->upload_id,
            'image_url'  => $imageUrl,
            'image'      => $imageUrl, // if your form previews from `image`

            'user'       => $item->user?->name,
            'created_at' => optional($item->created_at)->toDateTimeString(),
            'updated_at' => optional($item->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * Get KPI statistics for all inventory
     */
    public function getKpiStats(): array
    {
        $allItems = InventoryItem::select('id', 'minAlert')->get();
        $productIds = $allItems->pluck('id')->toArray();

        // Get bulk stock data for ALL products
        $stockData = app(StockEntryService::class)->bulkTotalStock($productIds);

        // Calculate KPIs
        $totalItems = count($allItems);
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $expiredCount = 0;
        $nearExpiryCount = 0;

        foreach ($allItems as $item) {
            $stock = $stockData[$item->id] ?? ['available' => 0, 'status' => null];
            $available = $stock['available'];
            $minAlert = $item->minAlert ?? 5;

            if ($available <= 0) {
                $outOfStockCount++;
            } elseif ($available > 0 && $available <= $minAlert) {
                $lowStockCount++;
            }

            // ✅ Count expiry status from already calculated data
            if ($stock['status'] === 'expired') {
                $expiredCount++;
            } elseif ($stock['status'] === 'near_expiry') {
                $nearExpiryCount++;
            }
        }

        return [
            'total_items' => $totalItems,
            'out_of_stock' => $outOfStockCount,
            'low_stock' => $lowStockCount,
            'expired' => $expiredCount,
            'near_expiry' => $nearExpiryCount,
        ];
    }
}
