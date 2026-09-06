<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RequeueFailedDelivery implements ShouldQueue
{
    use Queueable;

    protected Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
        // Run this job tomorrow
        $this->delay(now()->addDay());
    }

    public function handle(): void
    {
        if ($this->delivery->status !== 'failed') {
            return; // Status changed, skip
        }

        $this->delivery->update([
            'status' => 'pending',
            'driver_id' => null,
        ]);

        AuditLog::record('delivery.requeued', $this->delivery, [
            'reason' => 'Failed delivery re-queued for next day',
        ]);

        Log::info("Requeued failed delivery", [
            'delivery_id' => $this->delivery->id,
            'customer' => $this->delivery->customer_name,
        ]);
    }
}