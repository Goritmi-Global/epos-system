<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kitchen_order_id',
        'item_name',
        'variant_name',
        'quantity',
        'ingredients',
        'item_kitchen_note',
        'status',
    ];

    protected $casts = [
        'ingredients' => 'array',
    ];

    const STATUS_WAITING = 'Waiting';
    const STATUS_DONE = 'Done';
    const STATUS_CANCELLED = 'Cancelled';

    public function order()
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id');
    }
     public function kitchenOrder()
    {
        return $this->belongsTo(KitchenOrder::class);
    }
}

