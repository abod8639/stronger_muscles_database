<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = \App\Models\Category::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found! Please run CategorySeeder first.');

            return;
        }

        // Create 7-10 products per category
        foreach ($categories as $category) {
            \App\Models\Product::factory()
                ->count(rand(7, 10))
                ->create([
                    'category_id' => $category->id,
                ]);
        }

        $this->command->info('Products created successfully!');
    }
}
