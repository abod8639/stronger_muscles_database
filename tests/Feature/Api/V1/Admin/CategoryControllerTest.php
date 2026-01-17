<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $data = [
        'id' => 'cat-1',
        'name' => 'New Category',
        'description' => 'Test',
        'is_active' => true
    ];

    $response = $this->postJson('/api/v1/admin/categories', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'New Category']);
});

test('admin can delete a category if empty', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $category = Category::factory()->create();

    $response = $this->deleteJson('/api/v1/admin/categories/' . $category->id);

    $response->assertStatus(204);
});

test('admin cannot delete a category with products', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $response = $this->deleteJson('/api/v1/admin/categories/' . $category->id);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Cannot delete category with associated products']);
});

test('non-admin cannot manage categories', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/admin/categories', [])->assertStatus(403);
});
