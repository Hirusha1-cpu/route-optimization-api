<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_name', 'address', 'lat', 'lng',
        'window_start', 'window_end', 'cod_amount', 'status', 'driver_id',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'window_end' => 'datetime',
        ];
    }

    // Status State Machine
    public const TRANSITIONS = [
        'pending' => ['assigned'],
        'assigned' => ['in_transit'],
        'in_transit' => ['delivered', 'failed'],
        'delivered' => [],
        'failed' => ['pending'],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function codLedgerEntries()
    {
        return $this->hasMany(CodLedger::class);
    }
}