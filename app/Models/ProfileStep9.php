<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep9 extends Model
{
    protected $table = 'profile_step_9';
    protected $fillable = ['user_id','business_hours','auto_disable_after_hours'];
    protected $casts = [
        'business_hours' => 'array',
        'auto_disable_after_hours' => 'boolean',
    ];
}
