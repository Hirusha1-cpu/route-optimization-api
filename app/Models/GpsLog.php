<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['driver_id', 'lat', 'lng', 'logged_at'];

    protected function casts(): array
    {
        return ['logged_at' => 'datetime'];
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}