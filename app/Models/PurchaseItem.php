<?php

// app/Models/PurchaseItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'sub_total',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date', // Add this
    ];

    public function purchase()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(InventoryItem::class, 'product_id');
    }
    public function stockEntry()
    {
        return $this->hasOne(StockEntry::class, 'product_id', 'product_id')
            ->where('stock_type', 'stockin')
            ->select(['id', 'product_id', 'expiry_date']);
    }
}
