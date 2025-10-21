<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_order_type_id',
        'status',
        'order_time',
        'order_date',
        'note',
        'kitchen_note',
    ];

    const STATUS_WAITING   = 'Waiting';
    const STATUS_DONE      = 'Done';
    const STATUS_CANCELLED = 'Cancelled';

    public function posOrderType()
    {
        return $this->belongsTo(PosOrderType::class, 'pos_order_type_id');
    }

    public function items()
    {
        return $this->hasMany(KitchenOrderItem::class);
    }
}
