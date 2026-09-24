<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can create a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $category = Category::factory()->create();

    $productData = [
        'id' => 'prod-123',
        'name' => ['ar' => 'بروتين جديد', 'en' => 'New Protein'],
        'price' => 50.00,
        'description' => ['ar' => 'وصف البروتين', 'en' => 'Great protein'],
        'category_id' => $category->id,
        'stock_quantity' => 100,
    ];

    $response = $this->postJson('/api/v1/admin/products', $productData);

    $response->assertStatus(201)
        ->assertJsonFragment(['en' => 'New Protein']);

    $this->assertDatabaseHas('products', ['id' => 'prod-123']);
});

test('admin can update a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $product = Product::factory()->create();

    $updateData = [
        'name' => ['ar' => 'اسم معدل', 'en' => 'Updated Name'],
        'price' => 60.00,
    ];

    $response = $this->putJson("/api/v1/admin/products/{$product->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment(['en' => 'Updated Name']);

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('admin can delete a product', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $product = Product::factory()->create();

    $response = $this->deleteJson("/api/v1/admin/products/{$product->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('non-admin cannot manage products', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->getJson('/api/v1/admin/products')->assertStatus(401);
});
