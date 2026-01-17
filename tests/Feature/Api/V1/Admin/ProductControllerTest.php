<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a product', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $category = Category::factory()->create();

    $productData = [
        'id' => 'prod-123',
        'name' => 'New Protein',
        'price' => 50.00,
        'description' => 'Great protein',
        'category_id' => $category->id,
        'stock_quantity' => 100,
    ];

    $response = $this->postJson('/api/v1/admin/products', $productData);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'New Protein']);

    $this->assertDatabaseHas('products', ['id' => 'prod-123']);
});

test('admin can update a product', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $product = Product::factory()->create();

    $updateData = ['name' => 'Updated Name', 'price' => 60.00];

    $response = $this->putJson("/api/v1/admin/products/{$product->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Name']);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name']);
});

test('admin can delete a product', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $product = Product::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/products/{$product->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('non-admin can now manage products (temporary)', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/products')->assertStatus(200);
});
