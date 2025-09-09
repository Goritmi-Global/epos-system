<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function inventoryItems()
    {
        // pivot: inventory_item_tags
        return $this->belongsToMany(
            InventoryItem::class,
            'inventory_item_tags',
            'tag_id',
            'inventory_item_id'
        )->withTimestamps();
    }
}
