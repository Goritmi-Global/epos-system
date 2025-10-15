<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPromo extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'promo_id',
        'promo_name',
        'promo_type',
        'discount_amount',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'order_id');
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
