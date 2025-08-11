<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_step',
        'completed_steps',
        'is_completed',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'is_completed'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
