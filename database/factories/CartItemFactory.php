<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'CART-' . strtoupper(Str::random(10)),
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 200),
            'image_urls' => [fake()->imageUrl()],
            'quantity' => fake()->numberBetween(1, 5),
            'added_at' => now(),
            'flavors' => [fake()->flavor()],
            'size' => fake()->size(),
        ];
    }
}
