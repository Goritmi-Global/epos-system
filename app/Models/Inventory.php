<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class Inventory extends BaseModel
{
    protected $table = 'inventory_items';
    protected $appends = ['stock'];
    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit',
        'supplier',
        'category',
        'subcategory',
        'minAlert',
        'nutrition',
        'allergies',
        'tags',
        'image',
        'user_id',
        'upload_id'
    ];

    protected $casts = [
        'nutrition' => 'array',
        'allergies' => 'array',
        'tags'      => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class, 'product_id');
    }
    protected function stock(): Attribute
    {
        return Attribute::get(function () {
            $in = $this->stockEntries()->where('stock_type', 'stockin')->sum('quantity');
            $out = $this->stockEntries()->where('stock_type', 'stockout')->sum('quantity');
            return $in - $out;
        });
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }
    
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }

    // Relation to other models are defined here
    public function allergies()
    {
        return $this->belongsToMany(Allergy::class, 'inventory_item_allergies', 'inventory_item_id', 'allergy_id');
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'inventory_item_tags', 'inventory_item_id', 'tag_id');
    }
    public function calories()
    {
        return $this->belongsToMany(Calorie::class, 'inventory_item_calories', 'inventory_item_id', 'calorie_id');
    }
    public function fats()
    {
        return $this->belongsToMany(Fat::class, 'inventory_item_fats', 'inventory_item_id', 'fat_id');
    }
    public function carbs()
    {
        return $this->belongsToMany(Carb::class, 'inventory_item_carbs', 'inventory_item_id', 'carb_id');
    }
    public function proteins()
    {
        return $this->belongsToMany(Protein::class, 'inventory_item_proteins', 'inventory_item_id', 'protein_id');
    }



     

}
