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
        'discount_percentage', 
        'discount_name',       
        'order_items',
        'order_subtotal',
        'request_note',
        'approval_note',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'order_items' => 'array',
        'order_subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2', 
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function getDiscountAmountAttribute()
    {
        return round(($this->order_subtotal * $this->discount_percentage) / 100, 2);
    }

    // Relationships
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

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
