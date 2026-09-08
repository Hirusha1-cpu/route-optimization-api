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
        $company = Company::create(['name' => 'Login Test Company']);
        
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

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

    public function test_invalid_login_returns_error()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_logout_works()
    {
        $company = Company::create(['name' => 'Logout Test']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'logout@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens);
    }

    public function test_authenticated_user_can_get_their_info()
    {
        $company = Company::create(['name' => 'Me Test']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Me User',
            'email' => 'me@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'email' => 'me@test.com',
            'name' => 'Me User',
        ]);
    }
}