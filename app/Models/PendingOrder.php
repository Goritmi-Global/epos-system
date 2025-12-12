<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'phone_number',
        'delivery_location',
        'order_type',
        'table_number',
        'sub_total',
        'tax',
        'service_charges',
        'delivery_charges',
        'sale_discount',
        'promo_discount',
        'approved_discounts',
        'total_amount',
        'note',
        'kitchen_note',
        'order_items',
        'applied_promos',
        'approved_discount_details',
        'selected_discounts',
        'terminal_id',
        'held_at',
    ];

    protected $casts = [
        'order_items' => 'array',
        'applied_promos' => 'array',
        'approved_discount_details' => 'array',
        'selected_discounts' => 'array',
        'held_at' => 'datetime',
        'sub_total' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'delivery_charges' => 'decimal:2',
        'sale_discount' => 'decimal:2',
        'promo_discount' => 'decimal:2',
        'approved_discounts' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}