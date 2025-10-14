<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends BaseModel
{
    protected $table = 'inventory_items';

    protected $appends = ['stock'];

    protected $fillable = [
        'name',
        'minAlert',
        'sku',
        'description',
        'supplier_id',
        'unit_id',
        'user_id',
        'upload_id',
        'category_id',
    ];

    // ----- Belongs To -----
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }

    // ----- Many-to-Many (Pivots) -----
    public function allergies()
    {
        // pivot: inventory_item_allergies (inventory_item_id, allergy_id)
        return $this->belongsToMany(
            Allergy::class,
            'inventory_item_allergies',
            'inventory_item_id',
            'allergy_id'
        );
    }

    public function tags()
    {
        // pivot: inventory_item_tags (inventory_item_id, tag_id)
        return $this->belongsToMany(
            Tag::class,
            'inventory_item_tags',
            'inventory_item_id',
            'tag_id'
        );
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\InventoryCategory::class, 'category_id');
    }



    // ----- Nutrition: one row per inventory item in its own table -----
    public function nutrition()
    {
        // table: inventory_item_nutrition (inventory_item_id, calories, fat, carbs, protein, ...)
        return $this->hasOne(InventoryItemNutrition::class, 'inventory_item_id');
    }

    // ----- Stock & Purchases (as you had) -----
    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class, 'product_id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }
    public function ingredients()
    {
        return $this->hasMany(MenuIngredient::class, 'inventory_item_id');
    }


    protected function stock(): Attribute
    {
        return Attribute::get(function () {
            $in  = $this->stockEntries()->where('stock_type', 'stockin')->sum('quantity');
            $out = $this->stockEntries()->where('stock_type', 'stockout')->sum('quantity');
            return $in - $out;
        });
    }

    
}
