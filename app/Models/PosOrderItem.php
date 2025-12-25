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
        'amount_paid',
        'payment_status',
        'sale_discount_per_item',
        'note',
        'kitchen_note',
        'item_kitchen_note',
        'deal_id',
        'is_deal',
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

    public function addons()
    {
        return $this->hasMany(PosOrderItemAddon::class, 'pos_order_item_id');
    }

    public function getRemainingAmount()
    {
        return max(0, $this->price - $this->amount_paid);
    }

    public function updatePaymentStatus()
    {
        if ($this->amount_paid >= $this->price) {
            $this->payment_status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }
        $this->save();
    }
}
