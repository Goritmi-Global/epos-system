<?php

namespace App\Services\POS;

use App\Models\Inventory;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function list(array $filters = [])
    {
        return Inventory::with([
                'user:id,name',
                'allergies:id,name',
                'tags:id,name',
                'calories:id,value,label',
                'fats:id,grams,label',
                'carbs:id,grams,label',
                'proteins:id,grams,label',
            ])
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('name', 'like', "%$v%")
                       ->orWhere('sku', 'like', "%$v%");
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

                    // Relations (names + ids, ready for your modal)
                    'allergies'   => $item->allergies->pluck('name')->values(),
                    'allergy_ids' => $item->allergies->pluck('id')->values(),
                    'tags'        => $item->tags->pluck('name')->values(),
                    'tag_ids'     => $item->tags->pluck('id')->values(),

                    // Nutrition lookups via pivots
                    'calories'    => $item->calories->map(fn ($c) => ['id'=>$c->id,'value'=>$c->value,'label'=>$c->label])->values(),
                    'fats'        => $item->fats->map(fn ($f) => ['id'=>$f->id,'grams'=>$f->grams,'label'=>$f->label])->values(),
                    'carbs'       => $item->carbs->map(fn ($c) => ['id'=>$c->id,'grams'=>$c->grams,'label'=>$c->label])->values(),
                    'proteins'    => $item->proteins->map(fn ($p) => ['id'=>$p->id,'grams'=>$p->grams,'label'=>$p->label])->values(),

                    'user'        => $item->user?->name,
                    'image_url'   => UploadHelper::url($item->upload_id),
                    'created_at'  => $item->formatted_created_at,
                ];
            });
    }

    public function create(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            // Handle file upload -> uploads table
            if (!empty($data['image'])) {
                $upload = UploadHelper::store($data['image'], 'uploads', 'public');
                $data['upload_id'] = $upload->id;
            }
            unset($data['image']);

            $data['user_id'] = auth()->id();
            // Pull out pivot arrays before create
            $allergyIds = $data['allergy_ids']  ?? [];
            $tagIds     = $data['tag_ids']      ?? [];
            $calIds     = $data['calorie_ids']  ?? [];
            $fatIds     = $data['fat_ids']      ?? [];
            $carbIds    = $data['carb_ids']     ?? [];
            $protIds    = $data['protein_ids']  ?? [];

            unset(
                $data['allergy_ids'], $data['tag_ids'],
                $data['calorie_ids'], $data['fat_ids'],
                $data['carb_ids'],    $data['protein_ids']
            );

            $inventory = Inventory::create($data);

            // Sync pivots
            $inventory->allergies()->sync($allergyIds);
            $inventory->tags()->sync($tagIds);
            $inventory->calories()->sync($calIds);
            $inventory->fats()->sync($fatIds);
            $inventory->carbs()->sync($carbIds);
            $inventory->proteins()->sync($protIds);

            return $inventory->load([
                'allergies:id,name', 'tags:id,name',
                'calories:id,value,label', 'fats:id,grams,label',
                'carbs:id,grams,label', 'proteins:id,grams,label',
            ]);
        });
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            if (!empty($data['image'])) {
                $newUpload = UploadHelper::replace($inventory->upload_id, $data['image'], 'uploads', 'public');
                $data['upload_id'] = $newUpload->id;
            }
            unset($data['image']);

            // Extract pivot arrays if present (keep existing if not provided)
            $allergyIds = $data['allergy_ids']  ?? null;
            $tagIds     = $data['tag_ids']      ?? null;
            $calIds     = $data['calorie_ids']  ?? null;
            $fatIds     = $data['fat_ids']      ?? null;
            $carbIds    = $data['carb_ids']     ?? null;
            $protIds    = $data['protein_ids']  ?? null;

            unset(
                $data['allergy_ids'], $data['tag_ids'],
                $data['calorie_ids'], $data['fat_ids'],
                $data['carb_ids'],    $data['protein_ids']
            );

            $inventory->update($data);

            // Only sync when sent (lets you do partial updates)
            if (is_array($allergyIds)) $inventory->allergies()->sync($allergyIds);
            if (is_array($tagIds))     $inventory->tags()->sync($tagIds);
            if (is_array($calIds))     $inventory->calories()->sync($calIds);
            if (is_array($fatIds))     $inventory->fats()->sync($fatIds);
            if (is_array($carbIds))    $inventory->carbs()->sync($carbIds);
            if (is_array($protIds))    $inventory->proteins()->sync($protIds);

            return $inventory->fresh()->load([
                'allergies:id,name', 'tags:id,name',
                'calories:id,value,label', 'fats:id,grams,label',
                'carbs:id,grams,label', 'proteins:id,grams,label',
            ]);
        });
    }

    public function delete(Inventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            // With cascadeOnDelete on pivot FKs, detach is optional.
            $inventory->delete();
        });
    }
}
