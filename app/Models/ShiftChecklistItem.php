<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'type'
    ];

    public function shiftChecklists()
    {
        return $this->hasMany(ShiftChecklistItem::class, 'checklist_item_id');
    }
}
