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
            

            unset(
                $data['allergy_ids'], $data['tag_ids'],
              
            );

            $inventory = Inventory::create($data);

            // Sync pivots
            $inventory->allergies()->sync($allergyIds);
            $inventory->tags()->sync($tagIds);
            

            return $inventory->load([
                'allergies:id,name', 'tags:id,name',
                 
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
            

            unset(
                $data['allergy_ids'], $data['tag_ids'],
                
            );

            $inventory->update($data);

            // Only sync when sent (lets you do partial updates)
            if (is_array($allergyIds)) $inventory->allergies()->sync($allergyIds);
            if (is_array($tagIds))     $inventory->tags()->sync($tagIds);
             

            return $inventory->fresh()->load([
                'allergies:id,name', 'tags:id,name',
                 
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
