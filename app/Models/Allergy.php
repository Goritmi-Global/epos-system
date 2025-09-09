<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    protected $fillable = ['name', 'description'];

    public function inventoryItems()
    {
        // pivot: inventory_item_allergies
        return $this->belongsToMany(
            Inventory::class,
            'inventory_item_allergies',
            'allergy_id',
            'inventory_item_id'
        )->withTimestamps();
    }
}
