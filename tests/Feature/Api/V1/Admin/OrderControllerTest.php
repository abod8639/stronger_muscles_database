<?php

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can retrieve orders list', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    Order::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/orders');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data.data');
});

test('admin can filter orders by status', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'completed']);

    $response = $this->getJson('/api/v1/admin/orders?status=pending');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonFragment(['status' => 'pending']);
});

test('admin can view a specific order', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $order = Order::factory()->create();

    $response = $this->getJson('/api/v1/admin/orders/'.$order->id);

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $order->id]);
});

test('admin can update order status via patch orders/{id}', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patchJson('/api/v1/admin/orders/'.$order->id, [
        'status' => 'shipped',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['status' => 'shipped']);

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
});

test('admin can update order status via patch orders/{id}/status', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
        'status' => 'processing',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['status' => 'processing']);

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
});

test('cancelling order restores stock', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $product = Product::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => 'pending']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Test Product',
        'quantity' => 3,
        'unit_price' => 50,
        'subtotal' => 150,
    ]);

    $response = $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
        'status' => 'cancelled',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['status' => 'cancelled']);

    expect($product->fresh()->stock_quantity)->toBe(13);
});

test('updating with invalid status returns 422', function () {
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patchJson('/api/v1/admin/orders/'.$order->id.'/status', [
        'status' => 'invalid_status_xyz',
    ]);

    $response->assertStatus(422);
});

test('non-admin cannot access orders', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/orders')->assertStatus(401);
    $this->getJson('/api/v1/admin/orders/1')->assertStatus(401);
    $this->patchJson('/api/v1/admin/orders/1', ['status' => 'shipped'])->assertStatus(401);
    $this->patchJson('/api/v1/admin/orders/1/status', ['status' => 'shipped'])->assertStatus(401);
});
