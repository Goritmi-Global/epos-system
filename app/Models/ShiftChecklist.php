<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
          'type',
        'checklist_item_ids',
    ];

    protected $casts = [
        'checklist_item_ids' => 'array',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(ShiftChecklistItem::class, 'checklist_item_id');
    }
}
