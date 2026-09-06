<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendDeliveryConfirmation implements ShouldQueue
{
    use Queueable;

    protected Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function handle(): void
    {
        try {
            // In a real app, send SMS/email here
            Log::info("Delivery confirmed for {$this->delivery->customer_name}", [
                'delivery_id' => $this->delivery->id,
                'address' => $this->delivery->address,
            ]);

            // Simulate sending notification
            sleep(1); // Simulate external API call

            AuditLog::record('delivery.confirmation_sent', $this->delivery, [
                'customer' => $this->delivery->customer_name,
                'status' => 'sent',
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send delivery confirmation", [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
            ]);
            
            // Requeue with delay
            $this->release(300); // Retry after 5 minutes
        }
    }
}