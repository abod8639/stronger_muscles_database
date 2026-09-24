<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\Admin::ROLE_SUPER_ADMIN,
        ]);
    }

    public function inventoryManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\Admin::ROLE_INVENTORY_MANAGER,
        ]);
    }

    public function customerSupport(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => \App\Models\Admin::ROLE_CUSTOMER_SUPPORT,
        ]);
    }
}
