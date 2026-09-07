<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDeliveryConfirmation implements ShouldQueue
{
    // 💡 Laravel background optimization වලට SerializesModels trait එක අත්‍යවශ්‍ය වේ
    use Queueable, SerializesModels;

    public Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function handle(): void
    {
        try {
            // Real app එකකදී මෙතනට Twilio/Dialog SMS Gateway එකක් හෝ Email API එකක් සම්බන්ධ වේ.
            Log::info("Delivery confirmed for {$this->delivery->customer_name}", [
                'delivery_id' => $this->delivery->id,
                'address'     => $this->delivery->address,
            ]);

            // Interview simulation එකක් නිසා sleep(1) එක තබා ගනිමු.
            sleep(1); 

            // 💡 🚀 FIX: System-generated queue action එකක් නිසා explicitly 'actor' parameter එක යැවීම
            AuditLog::record('delivery.confirmation_sent', $this->delivery, [
                'customer' => $this->delivery->customer_name,
                'status'   => 'sent',
                'actor'    => 'system_queue' 
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send delivery confirmation", [
                'delivery_id' => $this->delivery->id,
                'error'       => $e->getMessage(),
            ]);
            
            // Retry handling
            $this->release(300); // Retry after 5 minutes
        }
    }
}
