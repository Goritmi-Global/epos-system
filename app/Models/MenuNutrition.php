<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuNutrition extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'calories',
        'protein',
        'fat',
        'carbs',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
