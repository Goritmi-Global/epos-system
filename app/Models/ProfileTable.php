<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileTable extends Model
{
    protected $table = 'profile_tables';

    protected $fillable = [
        'number_of_tables',
        'table_details',
    ];

    protected $casts = [
        'table_details' => 'array',
    ];

    public function step5Profiles()
    {
        return $this->hasMany(ProfileStep5::class, 'profile_table_id');
    }
}

