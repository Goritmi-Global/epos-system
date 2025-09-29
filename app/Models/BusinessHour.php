<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    // Add fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'day',
        'from',
        'to',
        'is_open',
        'break_from',
        'break_to',
    ];
}
