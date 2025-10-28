<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $table = 'meals';
    
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    // Check if meal is currently active based on time
    public function isCurrentlyActive(): bool
    {
        $currentTime = now()->format('H:i');
        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }

    // Scope for active meals at current time
    public function scopeActive($query)
    {
        $currentTime = now()->format('H:i');
        return $query->where('start_time', '<=', $currentTime)->where('end_time', '>=', $currentTime);
    }
    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_meal', 'meal_id', 'menu_item_id')->withTimestamps();
    }
    public function promoScopes()
    {
        return $this->belongsToMany(PromoScope::class, 'promo_scope_meal', 'meal_id', 'promo_scope_id');
    }


}