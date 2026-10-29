<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_name' => 'Stronger Muscles',
            'support_email' => fake()->safeEmail(),
            'support_phone' => fake()->phoneNumber(),
            'currency' => 'EGP',
            'delivery_fee' => 50,
            'free_delivery_threshold' => 0,
            'is_store_open' => true,
            'order_notifications_enabled' => true,
            'maintenance_mode' => false,
            'allowed_admin_roles' => StoreSetting::DEFAULT_ADMIN_ROLES,
        ];
    }

    /**
     * The store is closed and rejects new orders.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_store_open' => false,
        ]);
    }
}
