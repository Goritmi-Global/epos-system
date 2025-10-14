<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
    ];


    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }


    public function derivedUnits()
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'unit_id');
    }
}
