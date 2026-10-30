<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows customer to add item to cart and formats product properly for flutter', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'price' => 1500.00,
        'discount_price' => 1200.00,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/customer/cart', [
        'product_id' => $product->id,
        'quantity' => 2,
    ])
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('status', 'success')
            ->where('message', 'Item added to cart')
            ->has('data', fn (AssertableJson $json) => $json
                ->whereType('id', 'string')
                ->where('quantity', 2)
                ->whereType('price', 'double|integer')
                ->has('product', fn (AssertableJson $json) => $json
                    ->where('id', (string) $product->id)
                    ->whereType('price', 'double|integer')
                    ->whereType('discount_price', 'double|integer')
                    ->whereType('imageUrls', 'array')
                    ->etc())
                ->etc()));

    $this->getJson('/api/v1/customer/cart')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('status', 'success')
            ->has('data.0', fn (AssertableJson $json) => $json
                ->whereType('id', 'string')
                ->where('quantity', 2)
                ->whereType('price', 'double|integer')
                ->has('product', fn (AssertableJson $json) => $json
                    ->where('id', (string) $product->id)
                    ->whereType('price', 'double|integer')
                    ->whereType('discount_price', 'double|integer')
                    ->whereType('imageUrls', 'array')
                    ->etc())
                ->etc())
            ->etc());
});

