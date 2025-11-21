<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'amount_received',
        'payment_type',
        'payment_date',
        'payment_status',
        'code',
        'stripe_payment_intent_id',
        'last_digits',
        'brand',
        'currency_code',
        'exp_month',
        'exp_year',
        'cash_amount',
        'card_amount',

        'refund_status',
        'refund_amount',
        'refund_date',
        'refund_id',
        'refund_reason',
        'refunded_by',
    ];


    protected $casts = [
        'payment_date' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(PosOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
