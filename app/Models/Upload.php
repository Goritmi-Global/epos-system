<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_original_name',
        'file_name',
        'file_size',
        'extension',
        'type',
    ];

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'upload_id');
    }
    public function getFileUrlAttribute()
    {
        return asset('uploads/' . $this->file_name);
    }

}
