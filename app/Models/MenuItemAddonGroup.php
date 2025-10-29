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

    public function addonGroup()
    {
        return $this->belongsTo(AddonGroup::class);
    }
}