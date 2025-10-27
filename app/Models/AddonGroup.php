<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddonGroup extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'min_select',
        'max_select',
        'description',
        'status',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'min_select' => 'integer',
        'max_select' => 'integer',
        'status' => 'string',
    ];

    /**
     * Relationship: One group has many addons
     * Usage: $group->addons
     */
    public function addons()
    {
        return $this->hasMany(Addon::class)->orderBy('sort_order');
    }

    /**
     * Scope: Get only active groups
     * Usage: AddonGroup::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor: Get count of addons in this group
     * Usage: $group->addons_count
     */
    public function getAddonsCountAttribute()
    {
        return $this->addons()->count();
    }
}
