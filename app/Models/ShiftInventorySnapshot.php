<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftInventorySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'type',
        'user_id',
        'created_at',
    ];

    public $timestamps = false; 

    // Relationships
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

