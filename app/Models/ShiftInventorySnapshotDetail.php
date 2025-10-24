<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftInventorySnapshotDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'snap_id',
        'item_id',
        'stock_quantity',
    ];

    public function snapshot()
    {
        return $this->belongsTo(\App\Models\ShiftInventorySnapshot::class, 'snap_id');
    }

    public function item()
    {
        return $this->belongsTo(\App\Models\InventoryItem::class, 'item_id'); 
    }
}

