<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Admin RBAC and Alerts', function () {
    test('super admin can access users endpoint and delete products', function () {
        $superAdmin = Admin::factory()->superAdmin()->create();
        Sanctum::actingAs($superAdmin, ['*'], 'admin-api');

        User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $usersResponse = $this->getJson('/api/v1/admin/users');
        $usersResponse->assertSuccessful()
            ->assertJsonPath('status', 'success');

        $deleteResponse = $this->deleteJson("/api/v1/admin/products/{$product->id}");
        $deleteResponse->assertSuccessful()
            ->assertJsonPath('status', 'success');
    });

    test('customer support role cannot access users endpoint or delete products', function () {
        $supportAdmin = Admin::factory()->customerSupport()->create();
        Sanctum::actingAs($supportAdmin, ['*'], 'admin-api');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $usersResponse = $this->getJson('/api/v1/admin/users');
        $usersResponse->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'ليس لديك الصلاحية الكافية للوصول إلى هذا القسم.');

        $deleteResponse = $this->deleteJson("/api/v1/admin/products/{$product->id}");
        $deleteResponse->assertForbidden()
            ->assertJsonPath('status', 'error');

        // Verify product was not deleted
        expect(Product::find($product->id))->not->toBeNull();
    });

    test('inactive admin receives 403 forbidden', function () {
        $inactiveAdmin = Admin::factory()->create(['is_active' => false]);
        Sanctum::actingAs($inactiveAdmin, ['*'], 'admin-api');

        $response = $this->getJson('/api/v1/admin/profile');
        $response->assertForbidden()
            ->assertJsonPath('message', 'الحساب الإداري معطل.');
    });

    test('admin can retrieve real-time order alerts and pending counts', function () {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'admin-api');

        $user = User::factory()->create();
        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);
        Order::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'processing',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/admin/orders/alerts');

        $response->assertSuccessful()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.has_new_alerts', true)
            ->assertJsonPath('data.pending_count', 3)
            ->assertJsonPath('data.processing_count', 2);
    });
});

