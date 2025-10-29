<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItemAddonGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'addon_group_id',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
    public function addon()
    {
        return $this->belongsTo(Addon::class);
    }

    public function addonGroup()
    {
        return $this->belongsTo(AddonGroup::class);
    }
    public function addons()
    {
        return $this->hasManyThrough(
            Addon::class,        // Final model
            AddonGroup::class,   // Intermediate model
            'id',                // Foreign key on AddonGroup (local key)
            'addon_group_id',    // Foreign key on Addon table
            'addon_group_id',    // Local key on MenuItemAddonGroup
            'id'                 // Local key on AddonGroup
        );
    }
}