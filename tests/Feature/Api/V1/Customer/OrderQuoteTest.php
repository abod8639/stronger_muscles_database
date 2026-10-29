<?php

use App\Models\Address;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('prices the cart with the configured delivery fee and tax', function () {
    StoreSetting::factory()->create(['delivery_fee' => 30, 'tax_rate' => 14]);
    $product = Product::factory()->create(['price' => 100, 'discount_price' => null, 'stock_quantity' => 10]);

    $this->postJson('/api/v1/customer/orders/quote', [
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'success',
            'data' => [
                'subtotal' => 200,
                'shipping_cost' => 30,
                'tax_amount' => 28,
                'discount' => 0,
                'total_amount' => 258,
                'tax_rate' => 14,
                'free_delivery_threshold' => 0,
                'currency' => 'EGP',
                'is_accepting_orders' => true,
            ],
        ]);
});

it('uses the discounted and size specific prices that checkout charges', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'discount_price' => 80,
        'product_sizes' => [['size' => '2kg', 'price' => 150]],
    ]);

    $this->postJson('/api/v1/customer/orders/quote', [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $product->id, 'quantity' => 1, 'selected_size' => '2kg'],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', 230)
        ->assertJsonPath('data.total_amount', 280);
});

it('quotes the same total the order is then charged', function () {
    StoreSetting::factory()->create(['delivery_fee' => 25, 'free_delivery_threshold' => 1000, 'tax_rate' => 12.5]);
    $address = Address::factory()->create(['user_id' => $this->user->id]);
    $product = Product::factory()->create(['price' => 99.99, 'discount_price' => null, 'stock_quantity' => 10]);
    $items = [['product_id' => $product->id, 'quantity' => 3]];

    $quote = $this->postJson('/api/v1/customer/orders/quote', ['items' => $items])->json('data');

    $this->postJson('/api/v1/customer/orders', [
        'address_id' => $address->id,
        'payment_method' => 'cash',
        'items' => $items,
    ])
        ->assertCreated()
        ->assertJsonPath('data.shippingCost', $quote['shipping_cost'])
        ->assertJsonPath('data.taxAmount', $quote['tax_amount'])
        ->assertJsonPath('data.total_amount', $quote['total_amount']);

    expect($quote['tax_amount'])->toBe(37.5)
        ->and($quote['total_amount'])->toBe(362.47);
});

it('tells the app when the store is not accepting orders', function () {
    StoreSetting::factory()->closed()->create();
    $product = Product::factory()->create();

    $this->postJson('/api/v1/customer/orders/quote', [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_accepting_orders', false);
});

it('does not reserve stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->postJson('/api/v1/customer/orders/quote', [
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertSuccessful();

    expect($product->refresh()->stock_quantity)->toBe(5);
});

it('validates the quoted items', function (array $payload, string $field) {
    $this->postJson('/api/v1/customer/orders/quote', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'no items' => [['items' => []], 'items'],
    'unknown product' => [['items' => [['product_id' => 'missing', 'quantity' => 1]]], 'items.0.product_id'],
    'zero quantity' => [['items' => [['product_id' => 'x', 'quantity' => 0]]], 'items.0.quantity'],
]);

it('requires a signed in customer', function () {
    app('auth')->forgetGuards();

    $this->postJson('/api/v1/customer/orders/quote', ['items' => []])->assertUnauthorized();
});
