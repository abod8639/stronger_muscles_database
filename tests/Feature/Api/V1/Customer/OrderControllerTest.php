<?php

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('customer can place order and stock is decremented in database', function () {
    $user = User::factory()->create();
    $address = Address::create([
        'user_id' => $user->id,
        'full_name' => 'Test User',
        'phone' => '1234567890',
        'street' => '123 Main St',
        'city' => 'Cairo',
        'state' => 'Cairo',
        'postal_code' => '12345',
        'country' => 'Egypt',
        'is_default' => true,
    ]);
    $product = Product::factory()->create([
        'stock_quantity' => 15,
        'price' => 100,
        'total_sales' => 0,
    ]);

    Sanctum::actingAs($user);

    $payload = [
        'address_id' => $address->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/customer/orders', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Order placed successfully',
        ]);

    expect($product->fresh()->stock_quantity)->toBe(12);
    expect($product->fresh()->total_sales)->toBe(3);
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
});

test('order placement aggregates multiple items of same product and prevents overselling', function () {
    $user = User::factory()->create();
    $address = Address::create([
        'user_id' => $user->id,
        'full_name' => 'Test User',
        'phone' => '1234567890',
        'street' => '123 Main St',
        'city' => 'Cairo',
        'state' => 'Cairo',
        'postal_code' => '12345',
        'country' => 'Egypt',
        'is_default' => true,
    ]);
    $product = Product::factory()->create([
        'stock_quantity' => 5,
        'price' => 100,
    ]);

    Sanctum::actingAs($user);

    // Request total 6 (3 + 3) which exceeds stock 5
    $payload = [
        'address_id' => $address->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'selected_flavor' => 'Chocolate',
            ],
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'selected_flavor' => 'Vanilla',
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/customer/orders', $payload);

    $response->assertStatus(400);
    expect($product->fresh()->stock_quantity)->toBe(5);
});

test('customer order clears product cache', function () {
    $user = User::factory()->create();
    $address = Address::create([
        'user_id' => $user->id,
        'full_name' => 'Test User',
        'phone' => '1234567890',
        'street' => '123 Main St',
        'city' => 'Cairo',
        'state' => 'Cairo',
        'postal_code' => '12345',
        'country' => 'Egypt',
        'is_default' => true,
    ]);
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'price' => 100,
    ]);

    Cache::put("product:{$product->id}", 'cached_product_data', 3600);

    Sanctum::actingAs($user);

    $payload = [
        'address_id' => $address->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ];

    $this->postJson('/api/v1/customer/orders', $payload)->assertStatus(201);

    expect(Cache::has("product:{$product->id}"))->toBeFalse();
    expect($product->fresh()->stock_quantity)->toBe(8);
});
