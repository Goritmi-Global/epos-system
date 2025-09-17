<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrderType extends Model
{
    protected $fillable = [
        'pos_order_id',
        'order_type',
        'table_number',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }
}
