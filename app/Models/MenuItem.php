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
        'meal_id',
        'description',
        'status',
        'upload_id',
        'label_color',
        'is_taxable',
        'tax_percentage',
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
        return $this->belongsToMany(Allergy::class, 'menu_allergies')
            ->withPivot('type')
            ->withTimestamps();
    }

    // Tags (Many-to-Many via pivot)
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'menu_tags')->withTimestamps();
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'menu_item_meal', 'menu_item_id', 'meal_id')
            ->withTimestamps();
    }

    public function promoScopes()
    {
        return $this->belongsToMany(PromoScope::class, 'promo_scope_menu_item', 'menu_item_id', 'promo_scope_id');
    }

    public function variantPrices()
    {
        return $this->hasMany(MenuItemVariantPrice::class);
    }

    public function addonGroupRelations()
    {
        return $this->hasMany(MenuItemAddonGroup::class);
    }

    public function addonGroups()
    {
        return $this->belongsToMany(AddonGroup::class, 'menu_item_addon_groups')
            ->withTimestamps();
    }
}
