<?php

namespace App\Services\POS;

use App\Models\Inventory;
use App\Models\Allergy;
use App\Models\Tag;
use App\Helpers\UploadHelper;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function list(array $filters = [])
    {

        return Inventory::with(['user']) // you can add 'upload' if you create the relation
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('name', 'like', "%$v%")
                        ->orWhere('sku', 'like', "%$v%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->through(function ($item) {
                // Decode JSON fields safely
                $allergyIds = is_string($item->allergies) ? json_decode($item->allergies, true) : ($item->allergies ?? []);
                $tagIds     = is_string($item->tags)       ? json_decode($item->tags, true)      : ($item->tags ?? []);
                $nutrition  = is_string($item->nutrition)  ? json_decode($item->nutrition, true) : ($item->nutrition ?? []);

                $allergyNames = !empty($allergyIds)
                    ? Allergy::whereIn('id', $allergyIds)->pluck('name')->toArray()
                    : [];

                $tagNames = !empty($tagIds)
                    ? Tag::whereIn('id', $tagIds)->pluck('name')->toArray()
                    : [];

                return [
                    "id"          => $item->id,
                    "name"        => $item->name,
                    "category"    => $item->category,
                    "subcategory" => $item->subcategory,
                    "minAlert"    => $item->minAlert,
                    "unit"        => $item->unit,
                    "supplier"    => $item->supplier,
                    "sku"         => $item->sku,
                    "description" => $item->description,
                    "nutrition"   => $nutrition,
                    "allergies"   => $allergyNames,
                    "tags"        => $tagNames,
                    "user"        => $item->user?->name,

                    // Replaces old $item->image string. Provide a ready-to-use URL.
                    "image_url"   => UploadHelper::url($item->upload_id),

                    "created_at"  => $item->formatted_created_at,
                ];
            });
    }

    public function create(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            // Handle file upload -> uploads table
            if (isset($data['image']) && $data['image']) {
                $upload      = UploadHelper::store($data['image'], 'uploads', 'public');
                $data['upload_id'] = $upload->id;
            }
            // dd($data);

            unset($data['image']); // we never store raw path here

            $data['user_id'] = auth()->id();

            return Inventory::create($data);
        });
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            // Replace existing upload if a new image is provided
            if (isset($data['image']) && $data['image']) {
                $newUpload = UploadHelper::replace($inventory->upload_id, $data['image'], 'uploads', 'public');
                $data['upload_id'] = $newUpload->id;
            }

            unset($data['image']);

            $inventory->update($data);
            return $inventory->fresh();
        });
    }

    public function delete(Inventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            // (Optional) if you want to also remove the file on delete:
            // UploadHelper::delete($inventory->upload_id);
            $inventory->delete();
        });
    }
}
