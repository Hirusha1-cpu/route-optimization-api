<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_status_transition()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $delivery = Delivery::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->put("/api/deliveries/{$delivery->id}/status", [
                'status' => 'assigned'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('assigned', $delivery->fresh()->status);
    }

    public function test_invalid_status_transition_rejected()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $delivery = Delivery::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->put("/api/deliveries/{$delivery->id}/status", [
                'status' => 'delivered' // Can't skip assigned and in_transit
            ]);

        $response->assertStatus(422);
        $this->assertEquals('pending', $delivery->fresh()->status);
    }
}