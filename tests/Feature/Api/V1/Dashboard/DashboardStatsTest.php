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

    $response = $this->getJson('/api/v1/dashboard/users-stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'total_users',
            'users' => [
                '*' => [
                    'id',
                    'name',
                    'photo_url',
                    'has_ordered',
                    'orders_count',
                    'orders' => [
                        '*' => [
                            'id',
                            'status',
                            'total_amount',
                            'items' => [
                                '*' => [
                                    'product_name',
                                    'quantity',
                                    'image_url',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    // Verify user with order
    $userData = collect($response->json('users'))->firstWhere('id', $userWithOrder->id);
    expect($userData['has_ordered'])->toBeTrue()
        ->and($userData['orders'][0]['status'])->toBe('pending')
        ->and($userData['orders'][0]['items'][0]['product_name'])->toBe('Test Product');

    // Verify user without order
    $noOrderUserData = collect($response->json('users'))->firstWhere('id', $userWithoutOrder->id);
    expect($noOrderUserData['has_ordered'])->toBeFalse()
        ->and($noOrderUserData['orders'])->toBeEmpty();
});
