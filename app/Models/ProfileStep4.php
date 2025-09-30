<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep4 extends Model
{
    
    protected $table = 'profile_step_4';
    
    protected $fillable = [
        'user_id',
        'is_tax_registered',
        'tax_type',
        'tax_id',
        'tax_rate',
        'extra_tax_rates', 
        'price_includes_tax'
    ];
    
    protected $casts = [
        'is_tax_registered' => 'boolean',
        'price_includes_tax' => 'boolean',
        'tax_rate' => 'decimal:2',
    ];

}
