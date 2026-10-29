<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists the wishlist in the shape the flutter app parses', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'name' => ['ar' => 'واي بروتين', 'en' => 'Whey Protein'],
        'price' => 1200,
        'discount_price' => 999,
        'image_urls' => [['thumbnail' => 'thumb.jpg', 'medium' => 'medium.jpg', 'original' => 'original.jpg']],
    ]);
    WishlistItem::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customer/wishlist')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('status', 'success')
            ->has('data', 1, fn (AssertableJson $json) => $json
                ->whereType('id', 'string')
                ->where('userId', (string) $user->id)
                ->where('productId', $product->id)
                ->whereType('addedAt', 'string')
                ->where('productName', 'واي بروتين')
                ->where('productPrice', 999)
                ->where('productImageUrl', 'medium.jpg')));
});

it('only lists the authenticated user\'s wishlist', function () {
    WishlistItem::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/customer/wishlist')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('adds a product once even when it is added twice', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/customer/wishlist', ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.productId', $product->id);

    $this->postJson('/api/v1/customer/wishlist', ['product_id' => $product->id])
        ->assertOk();

    expect($user->wishlistItems()->count())->toBe(1);
});

it('rejects adding a product that does not exist', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/customer/wishlist', ['product_id' => 'PROD-MISSING'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});

it('removes a product by its product id', function () {
    $item = WishlistItem::factory()->create();
    Sanctum::actingAs($item->user);

    $this->deleteJson("/api/v1/customer/wishlist/{$item->product_id}")
        ->assertSuccessful();

    expect(WishlistItem::query()->find($item->id))->toBeNull();
});

it('does not remove another user\'s wishlist item', function () {
    $item = WishlistItem::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/v1/customer/wishlist/{$item->product_id}")
        ->assertSuccessful();

    expect(WishlistItem::query()->find($item->id))->not->toBeNull();
});

it('drops wishlist items when their product is deleted', function () {
    $item = WishlistItem::factory()->create();

    Product::query()->whereKey($item->product_id)->delete();

    expect(WishlistItem::query()->find($item->id))->toBeNull();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/customer/wishlist')->assertUnauthorized();
});
