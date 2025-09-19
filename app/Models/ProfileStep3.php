<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep3 extends Model
{
    protected $table = 'profile_step_3';
    protected $fillable = ['user_id','currency','currency_symbol_position','number_format','date_format','time_format'];
}
