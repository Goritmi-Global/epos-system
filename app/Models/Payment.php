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
