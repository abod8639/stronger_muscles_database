<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'البروتينات', 'description' => 'بروتينات لبناء العضلات والتعافي'],
            ['name' => 'الأحماض الأمينية', 'description' => 'أحماض أمينية لتعزيز الأداء والتعافي'],
            ['name' => 'الفيتامينات والمعادن', 'description' => 'فيتامينات ومعادن أساسية للصحة العامة'],
            ['name' => 'محفزات الطاقة', 'description' => 'منتجات لتعزيز الطاقة قبل التمرين'],
            ['name' => 'التعافي', 'description' => 'منتجات تساعد على التعافي بعد التمرين'],
            ['name' => 'حرق الدهون', 'description' => 'منتجات لدعم فقدان الوزن'],
            ['name' => 'الصحة العامة', 'description' => 'مكملات للصحة والعافية العامة'],
            ['name' => 'الكريمات', 'description' => 'كرياتين'],
        ];

        $category = fake()->randomElement($categories);

        return [
            'id' => fake()->uuid(),
            'name' => $category['name'],
            'description' => $category['description'],
            'image_url' => 'https://picsum.photos/seed/' . fake()->uuid() . '/300/200',
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => fake()->boolean(95),
        ];
    }
}
