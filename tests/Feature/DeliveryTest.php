<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_status_transition()
    {
        // 💡 1. Create a unified Company context for the tenant
        $company = Company::create(['name' => 'Fast Delivery Ltd']);

        $admin = User::create([
            'company_id' => $company->id,
            'name'       => 'Admin User',
            'email'      => 'admin@fast.com',
            'password'   => bcrypt('password123'),
            'role'       => 'admin',
        ]);

        $delivery = Delivery::create([
            'company_id'    => $company->id,
            'customer_name' => 'John Doe',
            'address'       => '123 Main St, Colombo',
            'lat'           => 6.9271,
            'lng'           => 79.8612,
            'window_start'  => '08:00',
            'window_end'    => '12:00',
            'cod_amount'    => 1500.00,
            'status'        => 'pending',
        ]);

        // 💡 Use putJson instead of put for standard API response behaviors
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
            'name'       => 'Admin User',
            'email'      => 'admin@fast.com',
            'password'   => bcrypt('password123'),
            'role'       => 'admin',
        ]);

        $delivery = Delivery::create([
            'company_id'    => $company->id,
            'customer_name' => 'Jane Doe',
            'address'       => '456 Galle Rd, Colombo',
            'lat'           => 6.9271,
            'lng'           => 79.8612,
            'window_start'  => '08:00',
            'window_end'    => '12:00',
            'cod_amount'    => 2500.00,
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/deliveries/{$delivery->id}/status", [
                'status' => 'delivered' // 🚨 Blocked! Cannot skip assigned and in_transit stages directly
            ]);

        $response->assertStatus(422);
        $this->assertEquals('pending', $delivery->fresh()->status);
    }

    /**
     * 💡 Multi-Tenancy Cross-Access Boundary Protection Check
     */
    public function test_company_a_admin_cannot_access_company_b_delivery()
    {
        $companyA = Company::create(['name' => 'Company A']);
        $companyB = Company::create(['name' => 'Company B']);

        $adminA = User::create([
            'company_id' => $companyA->id,
            'name'       => 'Admin A',
            'email'      => 'admina@test.com',
            'password'   => bcrypt('password123'),
            'role'       => 'admin',
        ]);

        $deliveryB = Delivery::create([
            'company_id'    => $companyB->id,
            'customer_name' => 'Company B Customer',
            'address'       => 'Kandy Rd, Malabe',
            'lat'           => 6.9271,
            'lng'           => 79.8612,
            'window_start'  => '09:00',
            'window_end'    => '17:00',
            'cod_amount'    => 500.00,
            'status'        => 'pending',
        ]);

        // Admin A tries to modify a delivery belonging to Company B
        $response = $this->actingAs($adminA)
            ->putJson("/api/deliveries/{$deliveryB->id}/status", [
                'status' => 'assigned'
            ]);

        // 🛡️ Global Scope forces a 404 ModelNotFoundException because Company A cannot query Company B records
        $response->assertStatus(404);
    }
}
