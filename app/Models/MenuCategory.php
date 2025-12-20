<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuCategory extends Model
{
    use HasFactory;

    protected $table = 'menu_categories';

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

    /**
     * Parent category relationship (self-referencing).
     */
    public function parent()
    {
        return $this->belongsTo(MenuCategory::class, 'parent_id');
    }

    /**
     * Children categories relationship.
     */
    public function children()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id');
    }

    /**
     * Alias for children so service/controller calls won't break.
     */
    public function subcategories()
    {
        return $this->children();
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'category_id', 'id');
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Upload::class, 'upload_id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'category_id');
    }
}
