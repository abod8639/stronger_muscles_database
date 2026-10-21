<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->randomFloat(1, 1, 5),
            'comment' => fake()->paragraph(),
            'is_verified_purchase' => fake()->boolean(60),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
