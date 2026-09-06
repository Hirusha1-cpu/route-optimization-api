<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register()
    {
        $response = $this->post('/api/register', [
            'role' => 'admin',
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'company_name' => 'Test Company',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
        $this->assertDatabaseHas('users', ['email' => 'admin@test.com', 'role' => 'admin']);
    }

    public function test_driver_can_register()
    {
        $company = \App\Models\Company::factory()->create();

        $response = $this->post('/api/register', [
            'role' => 'driver',
            'name' => 'Test Driver',
            'email' => 'driver@test.com',
            'password' => 'password123',
            'company_id' => $company->id,
            'phone' => '0771234567',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('drivers', ['name' => 'Test Driver', 'phone' => '0771234567']);
        $this->assertDatabaseHas('users', ['email' => 'driver@test.com', 'role' => 'driver']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
    }
}