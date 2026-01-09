<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productNames = [
            'بروتين واي',
            'كرياتين مونوهيدرات',
            'BCAA أحماض أمينية',
            'جلوتامين',
            'أوميجا 3',
            'فيتامين د3',
            'ملتي فيتامين',
            'بري وركاوت',
            'بوست وركاوت',
            'كارنيتين',
            'CLA حمض اللينوليك',
            'زنك مغنيسيوم',
            'كولاجين',
            'جينر ماس',
            'كازين بروتين',
        ];

        $brands = [
            'Optimum Nutrition',
            'MuscleTech',
            'BSN',
            'Cellucor',
            'Dymatize',
            'MyProtein',
            'Universal Nutrition',
            'Scitec Nutrition',
        ];

        $price = fake()->randomFloat(2, 50, 500);
        $hasDiscount = fake()->boolean(40);

        return [
            'id' => fake()->uuid(),
            'name' => fake()->randomElement($productNames) . ' - ' . fake()->randomElement(['نكهة الشوكولاتة', 'نكهة الفانيليا', 'نكهة الفراولة', 'بدون نكهة']),
            'price' => $price,
            'discount_price' => $hasDiscount ? $price * fake()->randomFloat(2, 0.7, 0.9) : null,
            'image_urls' => [
                'https://picsum.photos/seed/' . fake()->uuid() . '/400/400',
                'https://picsum.photos/seed/' . fake()->uuid() . '/400/400',
                'https://picsum.photos/seed/' . fake()->uuid() . '/400/400',
            ],
            'description' => fake()->paragraph(5),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'average_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'review_count' => fake()->numberBetween(5, 500),
            'brand' => fake()->randomElement($brands),
            'serving_size' => fake()->randomElement(['30g', '35g', '40g', '5g', '10g']),
            'servings_per_container' => fake()->randomElement([30, 60, 90, 100, 120]),
            'category_id' => \App\Models\Category::factory(),
            'is_active' => fake()->boolean(90),
            'flavors' => [fake()->randomElement(['نكهة الشوكولاتة', 'نكهة الفانيليا', 'نكهة الفراولة', 'بدون نكهة'])]
        ];
    }
}
