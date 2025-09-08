<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'product_name',
        'quantity',
        'cost',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
