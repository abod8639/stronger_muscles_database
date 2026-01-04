<?php

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create an order with items', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    expect($order->user->id)->toBe($user->id);
    expect($order->orderItems)->toHaveCount(1);
    expect($order->orderItems->first()->id)->toBe($orderItem->id);
    expect($order->orderItems->first()->product->id)->toBe($product->id);
});

it('can create a cart item for a user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $cartItem = CartItem::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    expect($user->cartItems)->toHaveCount(1);
    expect($user->cartItems->first()->id)->toBe($cartItem->id);
    expect($user->cartItems->first()->product->id)->toBe($product->id);
});

it('can access orders from user relationship', function () {
    $user = User::factory()->create();
    Order::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    expect($user->orders)->toHaveCount(3);
});
