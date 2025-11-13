<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'requested_by',
        'approved_by',
        'status',
        'discount_amount',
        'order_items',
        'order_subtotal',
        'request_note',
        'approval_note',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'order_items' => 'array',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}