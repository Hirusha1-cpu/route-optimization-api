<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_delivery()
    {
        $company = Company::create(['name' => 'Test Company']);
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $token = $admin->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/deliveries', [
                'customer_name' => 'John Doe',
                'address' => '123 Main St',
                'lat' => 6.9271,
                'lng' => 79.8612,
                'window_start' => '09:00',
                'window_end' => '10:00',
                'cod_amount' => 1500.00,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('deliveries', [
            'customer_name' => 'John Doe',
            'status' => 'pending',
        ]);
    }

    public function test_delivery_status_transition()
    {
        $company = Company::create(['name' => 'Fast Delivery Ltd']);

        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin@fast.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $delivery = Delivery::create([
            'company_id' => $company->id,
            'customer_name' => 'John Doe',
            'address' => '123 Main St, Colombo',
            'lat' => 6.9271,
            'lng' => 79.8612,
            'window_start' => now()->format('Y-m-d 09:00:00'),
            'window_end' => now()->format('Y-m-d 12:00:00'),
            'cod_amount' => 1500.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/deliveries/{$delivery->id}/status", [
                'status' => 'assigned'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('assigned', $delivery->fresh()->status);
    }

    public function test_invalid_status_transition_rejected()
    {
        $company = Company::create(['name' => 'Fast Delivery Ltd']);

        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin@fast.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $delivery = Delivery::create([
            'company_id' => $company->id,
            'customer_name' => 'Jane Doe',
            'address' => '456 Galle Rd, Colombo',
            'lat' => 6.9271,
            'lng' => 79.8612,
            'window_start' => now()->format('Y-m-d 09:00:00'),
            'window_end' => now()->format('Y-m-d 12:00:00'),
            'cod_amount' => 2500.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/deliveries/{$delivery->id}/status", [
                'status' => 'delivered'
            ]);

        $response->assertStatus(422);
        $this->assertEquals('pending', $delivery->fresh()->status);
    }

    public function test_company_a_admin_cannot_access_company_b_delivery()
    {
        $companyA = Company::create(['name' => 'Company A']);
        $companyB = Company::create(['name' => 'Company B']);

        $adminA = User::create([
            'company_id' => $companyA->id,
            'name' => 'Admin A',
            'email' => 'admina@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $deliveryB = Delivery::create([
            'company_id' => $companyB->id,
            'customer_name' => 'Company B Customer',
            'address' => 'Kandy Rd, Malabe',
            'lat' => 6.9271,
            'lng' => 79.8612,
            'window_start' => now()->format('Y-m-d 09:00:00'),
            'window_end' => now()->format('Y-m-d 17:00:00'),
            'cod_amount' => 500.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($adminA)
            ->putJson("/api/deliveries/{$deliveryB->id}/status", [
                'status' => 'assigned'
            ]);

        $response->assertStatus(404);
    }

    public function test_driver_can_only_update_own_deliveries()
    {
        $company = Company::create(['name' => 'Test Company']);
        
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Driver 1',
            'phone' => '0771234567',
        ]);

        $driverUser = User::create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'name' => 'Driver User',
            'email' => 'driver@test.com',
            'password' => bcrypt('password123'),
            'role' => 'driver',
        ]);

        $otherDriver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Driver 2',
            'phone' => '0771234568',
        ]);

        $delivery = Delivery::create([
            'company_id' => $company->id,
            'customer_name' => 'Test Customer',
            'address' => '123 Test St',
            'lat' => 6.9271,
            'lng' => 79.8612,
            'window_start' => now()->format('Y-m-d 09:00:00'),
            'window_end' => now()->format('Y-m-d 12:00:00'),
            'cod_amount' => 1000.00,
            'status' => 'assigned',
            'driver_id' => $otherDriver->id,
        ]);

        $response = $this->actingAs($driverUser)
            ->putJson("/api/deliveries/{$delivery->id}/status", [
                'status' => 'in_transit'
            ]);

        $response->assertStatus(403);
    }
}