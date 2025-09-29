<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep9 extends Model
{
    protected $table = 'profile_step_9';

    protected $fillable = [
        'user_id',
        'enable_loyalty_system',
        'enable_inventory_tracking',
        'enable_cloud_backup',
        'enable_multi_location',
        'theme_preference',
    ];

    protected $casts = [
        'enable_loyalty_system'     => 'boolean',
        'enable_inventory_tracking' => 'boolean',
        'enable_cloud_backup'       => 'boolean',
        'enable_multi_location'     => 'boolean',
    ];
}
