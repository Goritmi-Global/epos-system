<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrder extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'customer_name',
        'sub_total',
        'total_amount',
        'tax',
        'service_charges',
        'delivery_charges',
        'sales_discount',
        'approved_discounts',
        'status',
        'payment_status',
        'note',
        'kitchen_note',
        'order_date',
        'order_time',
        'source',

        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'refund_amount',
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
        return $this->hasMany(OrderPromo::class, 'order_id');
    }

    public function deliveryDetail()
    {
        return $this->hasOne(PosOrderDeliveryDetail::class, 'pos_order_id');
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function getTotalPaidAmount(): float
    {
        return (float) $this->payments()->sum('amount_received');
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->total_amount - $this->getTotalPaidAmount());
    }
}
