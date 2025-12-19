<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep9 extends Model
{
    protected $table = 'profile_step_9';

    protected $fillable = [
        // 'enable_loyalty_system',
        // 'enable_cloud_backup',
        // 'enable_multi_location',
        // 'theme_preference',
        'user_id',
        'enable_inventory_tracking',
        'logout_after_order',
        'logout_after_time',
        'logout_manual_only',
        'logout_time_minutes',
    ];

    protected $casts = [
        // 'enable_loyalty_system' => 'boolean',
        // 'enable_inventory_tracking' => 'boolean',
        // 'enable_cloud_backup' => 'boolean',
        // 'enable_multi_location' => 'boolean',
        'logout_after_order' => 'boolean',
        'logout_after_time' => 'boolean',
        'logout_manual_only' => 'boolean',
        'logout_time_minutes' => 'integer',
    ];
}
