<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    public $timestamps = false;
    protected $table = 'states';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'country_id',
        'country_code',
        'country_name',
        'state_code'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso2');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'state_code', 'state_code');
    }
}
