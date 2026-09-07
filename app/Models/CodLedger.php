<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CodLedger extends Model
{
    
    public $timestamps = true;
    const UPDATED_AT = null; 

    protected $fillable = ['driver_id', 'delivery_id', 'amount_collected'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('cod_ledger is append-only: entries cannot be updated.'));
        static::deleting(fn () => throw new RuntimeException('cod_ledger is append-only: entries cannot be deleted.'));
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}