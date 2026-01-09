<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'ITEM-' . strtoupper(Str::random(10)),
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'unit_price' => fake()->randomFloat(2, 10, 200),
            'quantity' => fake()->numberBetween(1, 5),
            'subtotal' => function (array $attributes) {
                return $attributes['unit_price'] * $attributes['quantity'];
            },
            'image_url' => fake()->imageUrl(),
            'flavor' => fake()->flavor(),
        ];
    }
}
