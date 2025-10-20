<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    protected $fillable = ['name', 'gmt_offset', 'is_default', 'country_id'];

    public function countries()
    {
        return $this->hasMany(Country::class, 'default_timezone_id');
    }
     public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
