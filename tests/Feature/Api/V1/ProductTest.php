<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list active products', function () {
    Product::factory()->count(5)->create(['is_active' => true]);
    Product::factory()->count(2)->create(['is_active' => false]);

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data.data')
        ->assertJsonStructure([
            'status',
            'data' => [
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'discountPrice',
                        'imageUrls',
                        'description',
                        'categoryId',
                        'isActive',
                        'flavors',
                        'size'
                    ]
                ]
            ]
        ]);
});

it('can filter products by category', function () {
    Product::factory()->create(['category_id' => 'cat1', 'is_active' => true]);
    Product::factory()->create(['category_id' => 'cat2', 'is_active' => true]);

    $response = $this->getJson('/api/v1/products?category=cat1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

it('can search products by name', function () {
    Product::factory()->create(['name' => 'Specific Product', 'is_active' => true]);
    Product::factory()->create(['name' => 'Other Product', 'is_active' => true]);

    $response = $this->getJson('/api/v1/products?search=Specific');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});
