<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantGroup extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
       
        'description',
        'status',
        'sort_order',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
       
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Relationship: One group has many variants
     * Usage: $group->variants
     * 
     * Example: "Size" group has "Small", "Medium", "Large" variants
     */
    public function variants()
    {
        return $this->hasMany(Variant::class)->orderBy('sort_order');
    }

    /**
     * Scope: Get only active groups
     * Usage: VariantGroup::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Accessor: Get count of variants in this group
     * Usage: $group->variants_count
     */
    public function getVariantsCountAttribute()
    {
        return $this->variants()->count();
    }

    /**
     * Check if this group allows multiple selections
     * Usage: $group->isMultiSelect()
     */
    public function isMultiSelect()
    {
        return $this->max_select > 1;
    }

    /**
     * Check if selection is required
     * Usage: $group->isRequired()
     */
    public function isRequired()
    {
        return $this->min_select > 0;
    }
}