<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep8 extends Model
{
    protected $table = 'profile_step_8';
    protected $fillable = ['user_id','attendance_policy'];
    protected $casts = [
        'attendance_policy' => 'array',
    ];
}
