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
        'price_includes_tax',
        
        // ADD THESE NEW FIELDS
        'has_service_charges',
        'service_charge_flat',
        'service_charge_percentage',
        'has_delivery_charges',
        'delivery_charge_flat',
        'delivery_charge_percentage',
    ];
    
    protected $casts = [
        'is_tax_registered' => 'boolean',
        'price_includes_tax' => 'boolean',
        'tax_rate' => 'decimal:2',
        
        // ADD THESE NEW CASTS
        'has_service_charges' => 'boolean',
        'service_charge_flat' => 'decimal:2',
        'service_charge_percentage' => 'decimal:2',
        'has_delivery_charges' => 'boolean',
        'delivery_charge_flat' => 'decimal:2',
        'delivery_charge_percentage' => 'decimal:2',
    ];

}