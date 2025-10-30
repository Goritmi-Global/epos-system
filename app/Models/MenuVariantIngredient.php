<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuVariantIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'variant_id',
        'inventory_item_id',
        'product_name',
        'quantity',
        'cost',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(MenuVariant::class, 'variant_id');
    }


    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}