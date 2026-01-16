<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'addresses' => [
                [
                    'label' => 'Home',
                    'full_name' => 'Test User',
                    'phone' => '1234567890',
                    'street' => '123 Test St',
                    'city' => 'Test City',
                    'state' => 'Test State',
                    'postal_code' => '12345',
                    'country' => 'Test Country',
                    'is_default' => true,
                    'latitude' => 123.456,
                    'longitude' => 789.012,
                ],
            ],

        ]);

        // Seed categories and products for the supplement store
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
