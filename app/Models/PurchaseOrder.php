<?php

// app/Models/PurchaseOrder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'total_amount',
        'purchase_date',
        'status',
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function items() {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }
}
