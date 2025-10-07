<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'message',
        'status',
        'is_read',
    ];

    /**
     * Relationship with InventoryItem (optional)
     */
    public function product()
    {
        return $this->belongsTo(InventoryItem::class, 'product_id');
    }
}
