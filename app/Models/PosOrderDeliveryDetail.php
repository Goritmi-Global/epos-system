<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderDeliveryDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_order_id',
        'phone_number',
        'delivery_location',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }
}
