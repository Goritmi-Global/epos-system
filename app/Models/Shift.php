<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_by',
        'start_time',
        'opening_cash',
        'cash_notes',
        'status',
        'end_time',
        'ended_by',
        'closing_cash',
        'sales_total',
    ];

    // Relationships
    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function ender()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
    public function details()
{
    return $this->hasMany(\App\Models\ShiftDetail::class, 'shift_id');
}

}
