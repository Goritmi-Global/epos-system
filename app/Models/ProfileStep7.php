<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep7 extends Model
{
    protected $table = 'profile_step_7';
    protected $fillable = ['user_id','cash_enabled','card_enabled','integrated_terminal','custom_payment_options','default_payment_method'];
    protected $casts = [
        'custom_payment_options' => 'array',
        'cash_enabled' => 'boolean',
        'card_enabled' => 'boolean',
    ];
}
