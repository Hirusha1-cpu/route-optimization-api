<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name'];

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}