<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep2 extends Model
{
    protected $table = 'profile_step_2';
    protected $fillable = ['user_id','business_name', 'business_type','legal_name','phone','email','address','website','upload_id'];

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }
}

