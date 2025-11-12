<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'discounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'discount_amount',
        'status',
        'start_date',
        'end_date',
        'min_purchase',
        'max_discount',
        'description',
    ];

    /**
     * The attributes that should be cast.
     * Convert date strings to Carbon instances and decimals to proper format
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Scope: Get all active discounts
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Check if discount is currently valid
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }

    /**
     * Get discount value formatted based on type
     * 
     * @return string
     */
    public function getFormattedDiscount(): string
    {
        if ($this->type === 'flat') {
            return '$' . number_format($this->discount_amount, 2);
        }
        
        return $this->discount_amount . '%';
    }
}