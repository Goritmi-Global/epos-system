<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;
    protected $table = 'countries';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'iso3',
        'iso2',
        'numeric_code',
        'phone_code',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'tld',
        'native',
        'region',
        'subregion',
        'languages',
        'default_timezone_id'
    ];

    protected $casts = [
        'languages' => 'array'
    ];

    public function states()
    {
        return $this->hasMany(State::class, 'country_code', 'iso2');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'country_code', 'iso2');
    }

    //  Correct one (default timezone)
    public function defaultTimezone()
    {
        return $this->belongsTo(Timezone::class, 'default_timezone_id');
    }
}
