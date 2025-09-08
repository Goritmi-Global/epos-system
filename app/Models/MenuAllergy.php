<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuAllergy extends Model
{
    use HasFactory;

    protected $table = 'menu_allergies';

    protected $fillable = [
        'menu_item_id',
        'allergy_id',
    ];
}
