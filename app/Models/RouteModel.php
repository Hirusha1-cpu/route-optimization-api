<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class RouteModel extends Model
{
    use BelongsToCompany;

    protected $table = 'routes';

    protected $fillable = [
        'company_id', 'driver_id', 'date', 'ordered_stops',
        'total_distance_km', 'total_duration_min', 'ai_summary',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'ordered_stops' => 'array',
        ];
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}