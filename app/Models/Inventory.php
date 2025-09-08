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
    public function allergies(){
        return $this->belongsTo(Allergy::class);
    }
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }


     

}
