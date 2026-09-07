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

    public Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
        // 💡 Delay එක මෙතනින් අයින් කර Controller එකෙන් dispatch කරන තැනට දැමීම වඩාත් සුදුසුයි.
    }

    public function handle(): void
    {
        // 💡 Background queue worker එකෙන් දුවද්දී Multi-tenancy scope එක බාධාවක් නොවීමට:
        $delivery = Delivery::withoutGlobalScopes()->find($this->delivery->id);

        if (!$delivery || $delivery->status !== 'failed') {
            return; 
        }

        $delivery->update([
            'status' => 'pending',
            'driver_id' => null,
        ]);

        // Background job එකක් නිසා actor එක 'system_queue' ලෙස සටහන් කිරීම
        AuditLog::record('delivery.requeued', $delivery, [
            'reason' => 'Failed delivery re-queued for next day',
            'actor'  => 'system_queue'
        ]);

        Log::info("Requeued failed delivery", [
            'delivery_id' => $delivery->id,
            'customer'    => $delivery->customer_name,
        ]);
    }
}
