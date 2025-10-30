<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVariant extends Model
{
    protected $fillable = ['menu_item_id', 'name', 'price'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function ingredients()
    {
        return $this->hasMany(MenuVariantIngredient::class, 'variant_id');
    }
}
