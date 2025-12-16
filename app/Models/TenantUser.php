<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TenantUser extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql'; // Central DB

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 
        'pin', 'email_verified_at', 'verifying_otp', 
        'is_tenant_owner'
    ];

    protected $hidden = [
        'password', 'pin', 'remember_token', 'verifying_otp'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_tenant_owner' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}