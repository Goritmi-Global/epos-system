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

        'is_saleable',
        'resale_type',
        'resale_value',
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

    public function variantIngredients()
    {
        return $this->hasMany(MenuVariantIngredient::class);
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

    public function addonPrices()
    {
        return $this->hasMany(MenuItemAddonGroup::class, 'menu_item_id');
    }

    /**
     * Check if this is a variant menu
     */
    public function isVariantMenu(): bool
    {
        return $this->variantPrices()->exists() || $this->variantIngredients()->exists();
    }

    /**
     * Get variant ingredients grouped by variant_id
     */
    public function getVariantIngredientsGroupedAttribute()
    {
        $grouped = [];

        foreach ($this->variantIngredients as $ingredient) {
            $variantId = $ingredient->variant_id;

            if (! isset($grouped[$variantId])) {
                $grouped[$variantId] = [];
            }

            $grouped[$variantId][] = [
                'id' => $ingredient->inventory_item_id,
                'inventory_item_id' => $ingredient->inventory_item_id,
                'product_name' => $ingredient->product_name,
                'name' => $ingredient->product_name,
                'quantity' => $ingredient->quantity,
                'qty' => $ingredient->quantity,
                'cost' => $ingredient->cost,
                'nutrition' => $ingredient->inventoryItem?->nutrition ?? null,
            ];
        }

        return $grouped;
    }

    public function variants()
    {
        return $this->hasMany(MenuVariant::class, 'menu_item_id');
    }

    public function getResalePriceAttribute(): float
    {
        if (! $this->is_saleable || ! $this->resale_type || ! $this->resale_value) {
            return 0;
        }

        if ($this->resale_type === 'flat') {
            return (float) $this->resale_value;
        }

        if ($this->resale_type === 'percentage') {
            return (float) ($this->price * ($this->resale_value / 100));
        }

        return 0;
    }

    /**
     * Get the deals that include this menu item
     */
    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'deal_menu_item')
            ->withTimestamps();
    }
}
