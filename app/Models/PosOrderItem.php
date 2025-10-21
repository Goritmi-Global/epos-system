<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $fillable = ['pos_order_id', 'menu_item_id', 'title', 'quantity', 'price', 'note', 'kitchen_note'];

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }
}
