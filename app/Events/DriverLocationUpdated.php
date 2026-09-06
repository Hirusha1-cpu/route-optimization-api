<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $driverId;
    public float $lat;
    public float $lng;
    public int $companyId;

    public function __construct(int $driverId, float $lat, float $lng, int $companyId)
    {
        $this->driverId = $driverId;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->companyId = $companyId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}.drivers"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}