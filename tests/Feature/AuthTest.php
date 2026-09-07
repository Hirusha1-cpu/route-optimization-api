<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register()
    {
        $response = $this->postJson('/api/register', [
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
        // 💡 Factory එක හරහා Company එකක් නිර්මාණය කිරීම
        $company = Company::create(['name' => 'Delivery Express']);

        $response = $this->postJson('/api/register', [
            'role' => 'driver',
            'name' => 'Test Driver',
            'email' => 'driver@test.com',
            'password' => 'password123',
            'company_id' => $company->id,
            'phone' => '0771234567',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('drivers', ['company_id' => $company->id, 'phone' => '0771234567']);
        $this->assertDatabaseHas('users', ['email' => 'driver@test.com', 'role' => 'driver']);
    }

    public function test_user_can_login()
    {
        // 💡 Tenant isolation නිසා මුලින්ම company එකක් සාදා User ව එයට අමුණන්න
        $company = Company::create(['name' => 'Login Test Company']);
        
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // 💡 post 대신 postJson භාවිතා කිරීම API testing වලදී වඩාත් සුදුසුයි
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'company_id', 'role'],
            'token'
        ]);
    }
}
