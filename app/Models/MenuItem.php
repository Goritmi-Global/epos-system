<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category_id',
        'description',
        'image',
    ];

    // Category
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    // Ingredients
    public function ingredients()
    {
        return $this->hasMany(MenuIngredient::class);
    }

    // Nutrition
    public function nutrition()
    {
        return $this->hasOne(MenuNutrition::class);
    }

    // Allergies (Many-to-Many via pivot)
    public function allergies()
    {
        return $this->belongsToMany(Allergy::class, 'menu_allergies');
    }

    // Tags (Many-to-Many via pivot)
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'menu_tags');
    }
}
