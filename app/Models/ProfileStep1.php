<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep1 extends Model
{
    protected $table = 'profile_step_1';
    protected $fillable = ['user_id','timezone','language','languages_supported'];
    protected $casts = [
        'languages_supported' => 'array',
    ];
}
