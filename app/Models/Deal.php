<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'description',
        'upload_id',
        'status',
        'is_taxable',
        'label_color',
        'category_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Get the menu items for this deal
     */
    // public function menuItems()
    // {
    //     return $this->belongsToMany(MenuItem::class, 'deal_menu_item')->withTimestamps();
    // }

    /**
     * Get the upload (image) for this deal
     */
    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Scope to get only active deals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get only inactive deals
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'deal_menu_item')->withPivot('quantity')->withTimestamps();
    }

    public function allergies()
    {
        return $this->belongsToMany(Allergy::class, 'deal_allergy')->withPivot('type')->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'deal_tag')->withTimestamps();
    }

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'deal_meal')->withTimestamps();
    }

    public function addonGroups()
    {
        return $this->belongsToMany(AddonGroup::class, 'deal_addon', 'deal_id', 'addon_group_id')->withTimestamps();
    }
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }
}
