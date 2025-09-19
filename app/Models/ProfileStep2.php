<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep2 extends Model
{
    protected $table = 'profile_step_2';
    protected $fillable = ['user_id','business_name','legal_name','phone','email','address','website','logo_path'];
}
