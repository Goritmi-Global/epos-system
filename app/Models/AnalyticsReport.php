<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsReport extends Model
{
    protected $fillable = ['key','label','payload','range']; // payload JSON
    protected $casts = ['payload' => 'array'];
}