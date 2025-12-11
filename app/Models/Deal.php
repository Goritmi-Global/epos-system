<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'description',
        'upload_id',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Get the menu items for this deal
     */
    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'deal_menu_item')->withTimestamps();
    }

    /**
     * Get the upload (image) for this deal
     */
    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Scope to get only active deals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get only inactive deals
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }
}
