<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = [
        'pos_order_id',
        'menu_item_id',
        'title',
        'quantity',
        'variant_name',
        'price',
        'sale_discount_per_item',
        'note',
        'kitchen_note',
        'item_kitchen_note',
        'deal_id',
        'is_deal'
    ];
    protected $casts = [
        'is_deal' => 'boolean',  
    ];

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }
    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }
}
