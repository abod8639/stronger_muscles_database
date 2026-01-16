<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard users stats returns correct data structure', function () {
    // Create users with and without orders
    $userWithOrder = User::factory()->create(['name' => 'User With Order']);
    $userWithoutOrder = User::factory()->create(['name' => 'User Without Order']);

    $product = Product::factory()->create(['name' => 'Test Product']);

    $order = Order::factory()->create([
        'user_id' => $userWithOrder->id,
        'status' => 'pending',
        'total_amount' => 100.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/dashboard/users-stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'total_users',
            'users' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'is_active',
                    'photo_url',
                    'total_spent',
                    'created_at',
                    'last_login',
                    'addresses',
                    'orders_count',
                ],
            ],
        ]);

    // Verify user with order
    $userData = collect($response->json('users'))->firstWhere('id', $userWithOrder->id);
    expect($userData['orders_count'])->toBe(1)
        ->and($userData['total_spent'])->toEqual(100.0)
        ->and($userData['role'])->toBe('user');

    // Verify user without order
    $noOrderUserData = collect($response->json('users'))->firstWhere('id', $userWithoutOrder->id);
    expect($noOrderUserData['orders_count'])->toBe(0)
        ->and($noOrderUserData['total_spent'])->toEqual(0.0);
});
