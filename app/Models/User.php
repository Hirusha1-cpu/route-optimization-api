<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, BelongsToCompany;

    protected $fillable = ['company_id', 'driver_id', 'name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function driverProfile()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }
}