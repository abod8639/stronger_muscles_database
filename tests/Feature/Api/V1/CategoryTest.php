<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('guest can list active categories sorted by sort_order', function () {
    // Create inactive category
    Category::factory()->create(['is_active' => false, 'name' => 'Inactive', 'sort_order' => 1]);

    // Create active categories with products
    $cat2 = Category::factory()->create(['is_active' => true, 'name' => 'Cat 2', 'sort_order' => 2]);
    $cat1 = Category::factory()->create(['is_active' => true, 'name' => 'Cat 1', 'sort_order' => 1]);

    // Add products to cat1
    Product::factory()->count(3)->create(['category_id' => $cat1->id]);

    $response = getJson('/api/v1/shop/categories');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data') // Only active ones
        ->assertJsonPath('data.0.name', 'Cat 1') // Sorted by sort_order
        ->assertJsonPath('data.1.name', 'Cat 2')
        ->assertJsonPath('data.0.productsCount', 3);
});

test('guest can see single active category', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->count(5)->create(['category_id' => $category->id]);

    $response = getJson("/api/v1/shop/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.productsCount', 5);
});

test('guest cannot see inactive category', function () {
    $category = Category::factory()->create(['is_active' => false]);

    $response = getJson("/api/v1/shop/categories/{$category->id}");

    $response->assertStatus(404);
});
