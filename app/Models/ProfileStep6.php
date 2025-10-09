<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileStep6 extends Model
{
    protected $table = 'profile_step_6';
    protected $fillable = ['user_id','receipt_header','receipt_footer','upload_id','show_qr_on_receipt','tax_breakdown_on_receipt','customer_printer', 'kot_printer','printers'];
    protected $casts = [
        'printers' => 'array',
        'show_qr_on_receipt' => 'boolean',
        'tax_breakdown_on_receipt' => 'boolean',
    ];
}
