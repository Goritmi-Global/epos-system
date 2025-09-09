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
    ];

    public function purchase() {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product() {
        return $this->belongsTo(InventoryItem::class, 'product_id');
    }
}
