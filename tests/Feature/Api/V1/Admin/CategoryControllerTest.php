<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can create a category', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $data = [
        'id' => 'cat-1',
        'name' => [
            'ar' => 'تصنيف جديد',
            'en' => 'New Category',
        ],
        'description' => [
            'ar' => 'وصف',
            'en' => 'Test',
        ],
        'is_active' => true,
    ];

    $response = $this->postJson('/api/v1/admin/categories', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['en' => 'New Category']);
});

test('admin can delete a category if empty', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $category = Category::factory()->create();

    $response = $this->deleteJson('/api/v1/admin/categories/'.$category->id);

    $response->assertStatus(204);
});

test('admin cannot delete a category with products', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $response = $this->deleteJson('/api/v1/admin/categories/'.$category->id);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Cannot delete category with associated products']);
});

test('non-admin cannot manage categories', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->postJson('/api/v1/admin/categories', [])->assertStatus(401);
});
