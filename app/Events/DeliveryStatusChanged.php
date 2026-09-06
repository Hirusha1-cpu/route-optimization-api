<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->delivery->company_id}.deliveries"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'status' => $this->delivery->status,
            'driver_id' => $this->delivery->driver_id,
        ];
    }
}