<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep1 extends Model
{
    protected $table = 'profile_step_1';
    protected $fillable = ['user_id','timezone_id','language','country_id'];

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }
}
