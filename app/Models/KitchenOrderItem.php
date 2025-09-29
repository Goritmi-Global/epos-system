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
    ];

    protected $casts = [
        'ingredients' => 'array', // so JSON is automatically converted to array
    ];

    public function order()
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id');
    }
}
