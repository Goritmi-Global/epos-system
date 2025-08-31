<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'address',
        'preferred_items',
        'contact',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
