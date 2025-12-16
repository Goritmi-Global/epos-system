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

     public function calculateStatus(): string
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            return 'Waiting';
        }

        $statuses = $items->pluck('status')->unique()->values();

        if ($statuses->count() === 1) {
            return $statuses->first();
        }

        if ($statuses->contains('In Progress')) {
            return 'In Progress';
        }

        if ($statuses->every(fn($s) => in_array($s, ['Done', 'Cancelled']))) {
            return 'Done';
        }
        return 'Waiting';
    }

    public function updateStatus(): void
    {
        $this->status = $this->calculateStatus();
        $this->save();
    }
}
