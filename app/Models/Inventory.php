<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
class Inventory extends Model
{
    protected $table = 'inventory_items';
    protected $appends = ['stock','formatted_created_at', 'formatted_updated_at'];
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
    public function allergies(){
        return $this->belongsTo(Allergy::class);
    }
    // Accessor for created_at
    public function getFormattedCreatedAtAttribute()
    {
        return Carbon::parse($this->created_at)->format('d-M-Y : h:iA');
    }

    // Accessor for updated_at
    public function getFormattedUpdatedAtAttribute()
    {
        return Carbon::parse($this->updated_at)->format('d-M-Y : h:iA');
    }

}
