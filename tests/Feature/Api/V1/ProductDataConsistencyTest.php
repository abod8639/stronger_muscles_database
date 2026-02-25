<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('shop and admin endpoints return consistent product data', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'is_active' => true,
        'name' => ['ar' => 'منتج اختبار', 'en' => 'Test Product'],
        'description' => ['ar' => 'وصف المنتج', 'en' => 'Product Description'],
        'serving_size' => '5g',
        'servings_per_container' => 20,
        'flavors' => ['Vanilla', 'Chocolate'],
        'product_sizes' => [
            ['size' => 'Small', 'price' => 100],
            ['size' => 'Large', 'price' => 200],
        ],
        'brand' => 'Test Brand',
        'featured' => true,
        'new_arrival' => true,
        'best_seller' => false,
    ]);

    // Get shop API response
    $shopResponse = getJson("/api/v1/shop/products/{$product->id}");
    $shopData = $shopResponse->json('data');

    // Get admin API response
    $adminResponse = getJson("/api/v1/admin/products/{$product->id}");
    $adminData = $adminResponse->json('data');

    // Verify shop response includes all product fields
    expect($shopData)
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('description')
        ->toHaveKey('brand')
        ->toHaveKey('serving_size')
        ->toHaveKey('servings_per_container')
        ->toHaveKey('flavors')
        ->toHaveKey('product_sizes')
        ->toHaveKey('featured')
        ->toHaveKey('new_arrival')
        ->toHaveKey('best_seller');

    // Verify description is not null
    expect($shopData['description'])->not->toBeNull();

    // Verify serving size is not null
    expect($shopData['serving_size'])->toBe('5g');

    // Verify flavors array has values
    expect($shopData['flavors'])->toContain('Vanilla', 'Chocolate');

    // Verify product_sizes array has values
    expect($shopData['product_sizes'])->toHaveCount(2);

    // Verify admin response has the same data
    expect($adminData['description'])->not->toBeNull();
    expect($adminData['serving_size'])->toBe('5g');
    expect($adminData['flavors'])->toContain('Vanilla', 'Chocolate');
    expect($adminData['product_sizes'])->toHaveCount(2);
});

test('shop list endpoint includes all product attributes', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'name' => ['ar' => 'منتج', 'en' => 'Product'],
        'description' => ['ar' => 'وصف', 'en' => 'Description'],
        'serving_size' => '3g',
        'servings_per_container' => 30,
        'flavors' => ['Lemon'],
        'featured' => true,
        'new_arrival' => false,
    ]);

    $response = getJson('/api/v1/shop/products');
    $data = $response->json('data.data.0');

    expect($data)
        ->toHaveKey('description')
        ->toHaveKey('serving_size')
        ->toHaveKey('servings_per_container')
        ->toHaveKey('flavors')
        ->toHaveKey('featured')
        ->toHaveKey('new_arrival');

    expect($data['description'])->not->toBeNull();
    expect($data['serving_size'])->toBe('3g');
    expect($data['flavors'])->toContain('Lemon');
});
