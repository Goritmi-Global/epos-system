<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'variant_group_id',
        'price_modifier',
        'description',
        'status',
        'sort_order',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'price_modifier' => 'decimal:2',
        'variant_group_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Relationship: Each variant belongs to one group
     * Usage: $variant->variantGroup
     * 
     * Example: "Small" variant belongs to "Size" group
     */
    public function variantGroup()
    {
        return $this->belongsTo(VariantGroup::class);
    }

    /**
     * Scope: Get only active variants
     * Usage: Variant::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor: Get formatted price modifier
     * Usage: $variant->formatted_price_modifier
     * Returns: "+£2.50" or "-£1.00" or "£0.00"
     */
    public function getFormattedPriceModifierAttribute()
    {
        $price = number_format(abs($this->price_modifier), 2);
        
        if ($this->price_modifier > 0) {
            return '+£' . $price;
        } elseif ($this->price_modifier < 0) {
            return '-£' . $price;
        }
        
        return '£' . $price;
    }

    /**
     * Check if this variant has additional cost
     * Usage: $variant->hasAdditionalCost()
     */
    public function hasAdditionalCost()
    {
        return $this->price_modifier != 0;
    }
}