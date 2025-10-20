<?php

// =================================================================
// Category Model - app/Models/Category.php
// =================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'upload_id',
        'active',
        'parent_id',
        'total_value',
        'total_items',
        'out_of_stock',
        'low_stock',
        'in_stock',
    ];

    protected $casts = [
        'active' => 'boolean',
        'total_value' => 'decimal:2',
        'total_items' => 'integer',
        'out_of_stock' => 'integer',
        'low_stock' => 'integer',
        'in_stock' => 'integer',
    ];

    /**
     * Get the subcategories for the category
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(InventoryCategory::class, 'parent_id');
    }

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'parent_id');
    }

    /**
     * Scope to get only parent categories
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only subcategories
     */
    public function scopeSubcategories($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope to get active categories
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if category is a parent category
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if category has subcategories
     */
    public function hasSubcategories(): bool
    {
        return $this->subcategories()->exists();
    }

    public function primaryInventoryItems()
    {
        return $this->hasMany(\App\Models\InventoryItem::class, 'category_id');
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Upload::class, 'upload_id');
    }
}
