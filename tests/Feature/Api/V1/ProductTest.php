<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('guest can list active products with pagination', function () {
    Product::factory()->count(25)->create(['is_active' => true]);
    Product::factory()->count(5)->create(['is_active' => false]);

    $response = getJson('/api/v1/shop/products');

    $response->assertStatus(200)
        ->assertJsonCount(20, 'data.data') // Page size is 20
        ->assertJsonStructure([
            'status',
            'data' => [
                'meta' => [
                    'current_page',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'brand',
                        'categoryName'
                    ]
                ]
            ]
        ]);
});

test('guest can filter products by category', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
    Product::factory()->create(['is_active' => true]);

    $response = getJson("/api/v1/shop/products?category={$category->id}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

test('guest can search products by name and brand', function () {
    Product::factory()->create(['name' => 'Gold Whey', 'brand' => 'Optimum', 'is_active' => true]);
    Product::factory()->create(['name' => 'Iso 100', 'brand' => 'Dymatize', 'is_active' => true]);

    // Search by name
    getJson('/api/v1/shop/products?search=Gold')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // Search by brand
    getJson('/api/v1/shop/products?search=Dymatize')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

test('guest can sort products', function () {
    Product::factory()->create(['price' => 100, 'created_at' => now()->subDay(), 'is_active' => true]);
    Product::factory()->create(['price' => 50, 'created_at' => now(), 'is_active' => true]);

    // Price Low to High
    getJson('/api/v1/shop/products?sort_by=price_low')
        ->assertStatus(200)
        ->assertJsonPath('data.data.0.price', 50);

    // Price High to Low
    getJson('/api/v1/shop/products?sort_by=price_high')
        ->assertStatus(200)
        ->assertJsonPath('data.data.0.price', 100);
});

test('guest can see single active product with category', function () {
    $category = Category::factory()->create(['name' => 'Supplements']);
    $product = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

    $response = getJson("/api/v1/shop/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $product->name)
        ->assertJsonPath('data.categoryName', 'Supplements');
});

test('guest gets 404 for inactive or missing product', function () {
    $product = Product::factory()->create(['is_active' => false]);

    getJson("/api/v1/shop/products/{$product->id}")
        ->assertStatus(404)
        ->assertJsonPath('status', 'error');

    getJson("/api/v1/shop/products/non-existent")
        ->assertStatus(404);
});
