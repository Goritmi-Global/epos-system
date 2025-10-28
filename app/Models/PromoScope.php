<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoScope extends Model
{
    protected $fillable = [];

    // Load relations by default
    protected $with = ['promos', 'meals', 'menuItems'];

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'scope_promo', 'promo_scope_id', 'promo_id');
    }

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'promo_scope_meal', 'promo_scope_id', 'meal_id');
    }

    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'promo_scope_menu_item', 'promo_scope_id', 'menu_item_id');
    }
}
