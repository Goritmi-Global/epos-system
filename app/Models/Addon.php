<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'addon_group_id',
        'price',
        'description',
        'status',
        'sort_order',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'price' => 'decimal:2',
        'addon_group_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Relationship: Each addon belongs to one group
     * Usage: $addon->addonGroup
     */
    public function addonGroup()
    {
        return $this->belongsTo(AddonGroup::class);
    }

    /**
     * Scope: Get only active addons
     * Usage: Addon::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor: Get formatted price
     * Usage: $addon->formatted_price
     */
    public function getFormattedPriceAttribute()
    {
        return '£' . number_format($this->price, 2);
    }

    public function orderItemAddons()
    {
        return $this->hasMany(PosOrderItemAddon::class, 'addon_id');
    }
}
