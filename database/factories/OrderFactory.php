<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'order_date' => now(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'card',
            'address_id' => 'addr-' . Str::random(5),
            'shipping_address_snapshot' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => fake()->country(),
            ],
            'subtotal' => fake()->randomFloat(2, 50, 500),
            'shipping_cost' => fake()->randomFloat(2, 0, 50),
            'discount' => fake()->randomFloat(2, 0, 20),
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal'] + $attributes['shipping_cost'] - $attributes['discount'];
            },
            'tracking_number' => 'TRK' . strtoupper(Str::random(10)),
            'notes' => fake()->sentence(),
        ];
    }
}
