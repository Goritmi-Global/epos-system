<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockEntry extends Model
{
    use HasFactory;

    // Table name (optional if it matches the plural of model)
    protected $table = 'stock_entries';

    // Mass assignable attributes
    protected $fillable = [
        'category_id',
        'supplier_id',
        'product_id',
        'user_id',
        'quantity',
        'price',
        'value',
        'operation_type',
        'stock_type',
        'expiry_date',
        'description',
        'purchase_date',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Inventory::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
