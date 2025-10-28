<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItemVariantPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'variant_id',
        'price',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}

