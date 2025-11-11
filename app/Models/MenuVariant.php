<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVariant extends Model
{
    protected $fillable = ['menu_item_id', 'name', 'price', 'is_saleable', 'resale_type', 'resale_value'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function ingredients()
    {
        return $this->hasMany(MenuVariantIngredient::class, 'variant_id');
    }

    public function variants()
    {
        return $this->hasMany(MenuVariant::class, 'menu_item_id');
    }

    /**
     * ✅ Get all variants with resale information
     */
    public function getVariantsWithResaleAttribute()
    {
        return $this->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->name,
                'price' => $variant->price,
                'is_saleable' => $variant->is_saleable,
                'resale_type' => $variant->resale_type,
                'resale_value' => $variant->resale_value,
                'resale_price' => $variant->resale_price,
                'display_name' => $variant->display_name,
            ];
        });
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
     * ✅ NEW: Get display name with resale info
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;

        if ($this->is_saleable) {
            if ($this->resale_type === 'flat') {
                $name .= " (Resale: ₹{$this->resale_value})";
            } elseif ($this->resale_type === 'percentage') {
                $name .= " (Resale: {$this->resale_value}%)";
            }
        }

        return $name;
    }
}
