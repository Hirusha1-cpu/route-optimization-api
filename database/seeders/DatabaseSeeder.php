<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test company
        $company = Company::create([
            'name' => 'Colombo Express Couriers',
        ]);

        // Create admin user
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // Create driver
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Driver 1',
            'phone' => '0771234567',
            'wallet_balance' => 0,
        ]);

        // Create driver user
        User::create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'name' => 'Driver User',
            'email' => 'driver@test.com',
            'password' => bcrypt('password123'),
            'role' => 'driver',
        ]);

        // Create sample deliveries
        $sampleDeliveries = [
            [
                'customer_name' => 'John Doe',
                'address' => '123 Galle Road, Colombo 3',
                'lat' => 6.9121,
                'lng' => 79.8430,
                'window_start' => '09:00',
                'window_end' => '10:00',
                'cod_amount' => 1500.00,
            ],
            [
                'customer_name' => 'Mary Smith',
                'address' => '45 Park Street, Colombo 7',
                'lat' => 6.9120,
                'lng' => 79.8577,
                'window_start' => '10:00',
                'window_end' => '11:00',
                'cod_amount' => 2000.00,
            ],
            [
                'customer_name' => 'Peter Perera',
                'address' => '78 Havelock Road, Colombo 5',
                'lat' => 6.8890,
                'lng' => 79.8520,
                'window_start' => '11:00',
                'window_end' => '12:00',
                'cod_amount' => 750.00,
            ],
            [
                'customer_name' => 'Sunil Fernando',
                'address' => '22 Kandy Road, Colombo 8',
                'lat' => 6.9355,
                'lng' => 79.8470,
                'window_start' => '13:00',
                'window_end' => '14:00',
                'cod_amount' => 1800.00,
            ],
            [
                'customer_name' => 'Kamala Jayawardena',
                'address' => '56 Marine Drive, Colombo 6',
                'lat' => 6.8900,
                'lng' => 79.8390,
                'window_start' => '14:00',
                'window_end' => '15:00',
                'cod_amount' => 1200.00,
            ],
        ];

        foreach ($sampleDeliveries as $data) {
            \App\Models\Delivery::create([
                'company_id' => $company->id,
                'customer_name' => $data['customer_name'],
                'address' => $data['address'],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'window_start' => now()->format('Y-m-d') . ' ' . $data['window_start'] . ':00',
                'window_end' => now()->format('Y-m-d') . ' ' . $data['window_end'] . ':00',
                'cod_amount' => $data['cod_amount'],
                'status' => 'pending',
            ]);
        }
    }
}
