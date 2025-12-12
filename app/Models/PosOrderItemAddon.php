<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItemAddon extends Model
{
    protected $table = 'pos_order_item_addons';

    protected $fillable = [
        'pos_order_item_id',
        'addon_id',
        'addon_name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Relationship: Belongs to PosOrderItem
     */
    public function orderItem()
    {
        return $this->belongsTo(PosOrderItem::class, 'pos_order_item_id');
    }

    /**
     * Relationship: Belongs to Addon
     */
    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}