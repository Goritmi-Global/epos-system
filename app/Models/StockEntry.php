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
        'supplier_id', // product id store as an inventory item id 
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

    protected $casts = [
        'quantity'      => 'decimal:2',
        'price'         => 'decimal:2',
        'value'         => 'decimal:2',
        'expiry_date'   => 'date',
        'purchase_date' => 'date',
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
        return $this->belongsTo(InventoryItem::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

     // --- Scopes (handy for queries/aggregates) ---
    public function scopeForItem($q, int $itemId)
    {
        return $q->where('product_id', $itemId);
    }

    public function scopeStockIn($q)
    {
        return $q->where('stock_type', 'stockin');
    }

    public function scopeStockOut($q)
    {
        return $q->where('stock_type', 'stockout');
    }

    // app/Models/StockEntry.php
    public function allocationsUsedFromMe()   // for a stock-in batch: who consumed me?
    {
        return $this->hasMany(StockOutAllocation::class, 'stock_in_entry_id');
    }
    public function allocationsForThisOut()   // for a stock-out entry: which IN batches did I eat?
    {
        return $this->hasMany(StockOutAllocation::class, 'stock_out_entry_id');
    }

}
