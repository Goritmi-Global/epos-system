<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'sales_amount',
    ];

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

