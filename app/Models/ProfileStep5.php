<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep5 extends Model
{
    protected $table = 'profile_step_5';
    protected $fillable = ['user_id','order_types','table_management_enabled','online_ordering_enabled','profile_table_id'];
    protected $casts = [
        'order_types' => 'array',
        'table_details' => 'array',
        'table_management_enabled' => 'boolean',
        'online_ordering_enabled' => 'boolean',
    ];

     public function profileTable()
    {
        return $this->belongsTo(ProfileTable::class, 'profile_table_id');
    }
}
