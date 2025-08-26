<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'sku','name','description','unit','price','cost','stock','min_stock','status'
    ];
}
