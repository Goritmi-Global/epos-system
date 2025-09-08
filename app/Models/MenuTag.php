<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTag extends Model
{
    use HasFactory;

    protected $table = 'menu_tags';

    protected $fillable = [
        'menu_item_id',
        'tag_id',
    ];
}
