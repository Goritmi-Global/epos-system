<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory_items';
    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit',
        'supplier',
        'category',
        'subcategory',
        'minAlert',
        'nutrition',
        'allergies',
        'tags',
        'image',
        'user_id',
    ];

    protected $casts = [
        'nutrition' => 'array',
        'allergies' => 'array',
        'tags'      => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
