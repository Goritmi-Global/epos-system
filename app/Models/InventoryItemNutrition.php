<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItemNutrition extends Model
{
    use HasFactory;

    protected $table = 'inventory_item_nutrition';

    protected $fillable = [
        'inventory_item_id',
        'calories',
        'protein',
        'fat',
        'carbs',
    ];

    /**
     * Relationship: Each nutrition record belongs to one inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
