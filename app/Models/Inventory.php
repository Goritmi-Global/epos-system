<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Inventory extends BaseModel
{
    protected $table = 'inventory_items';

    protected $appends = ['stock'];

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit',        // scalar field (e.g., "kg", "ltr", etc.)
        'supplier',    // scalar field (e.g., "ABC Traders")
        'minAlert',
        'image',
        'user_id',
        'upload_id',
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
        )->withTimestamps();
    }

    public function tags()
    {
        // pivot: inventory_item_tags (inventory_item_id, tag_id)
        return $this->belongsToMany(
            Tag::class,
            'inventory_item_tags',
            'inventory_item_id',
            'tag_id'
        )->withTimestamps();
    }

    public function categories()
    {
        // pivot: inventory_item_categories (inventory_item_id, category_id)
        return $this->belongsToMany(
            InventoryCategory::class,
            'inventory_item_categories',
            'inventory_item_id',
            'category_id'
        )->withTimestamps();
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

    protected function stock(): Attribute
    {
        return Attribute::get(function () {
            $in  = $this->stockEntries()->where('stock_type', 'stockin')->sum('quantity');
            $out = $this->stockEntries()->where('stock_type', 'stockout')->sum('quantity');
            return $in - $out;
        });
    }
}
