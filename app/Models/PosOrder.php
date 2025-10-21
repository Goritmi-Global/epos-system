<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrder extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'sub_total',
        'total_amount',
        'tax',
        'service_charges',
        'delivery_charges',
        'status',
        'note',
        'kitchen_note',
        'order_date',
        'order_time',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->hasOne(PosOrderType::class, 'pos_order_id');
    }
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
    public function items()
    {
        return $this->hasMany(PosOrderItem::class, 'pos_order_id');
    }
    public function kot()
    {
        return $this->hasOne(KitchenOrder::class, 'pos_order_type_id', 'id');
    }
    public function promo()
    {
        return $this->hasOne(OrderPromo::class, 'order_id');
    }
}
