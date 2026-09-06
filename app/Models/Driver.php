<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'wallet_balance'];

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function gpsLogs()
    {
        return $this->hasMany(GpsLog::class);
    }

    public function codLedgerEntries()
    {
        return $this->hasMany(CodLedger::class);
    }

    public function calculatedWalletBalance(): float
    {
        return (float) $this->codLedgerEntries()->sum('amount_collected');
    }
}