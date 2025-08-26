<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    protected $fillable = [
        'order_no','customer_name','service_type','table_no','status','total','paid','change'
    ];
}