<?php

use App\Models\User;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can retrieve orders list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    Order::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/orders');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data.data');
});

test('admin can filter orders by status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'completed']);

    $response = $this->getJson('/api/v1/admin/orders?status=pending');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonFragment(['status' => 'pending']);
});

test('admin can view a specific order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $order = Order::factory()->create();

    $response = $this->getJson('/api/v1/admin/orders/' . $order->id);

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $order->id]);
});

test('admin can update order status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patchJson('/api/v1/admin/orders/' . $order->id, [
        'status' => 'shipped'
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['status' => 'shipped']);

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
});

test('non-admin cannot access orders', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/orders')->assertStatus(403);
    $this->getJson('/api/v1/admin/orders/1')->assertStatus(403);
    $this->patchJson('/api/v1/admin/orders/1', ['status' => 'shipped'])->assertStatus(403);
});
