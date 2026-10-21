<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('guest can list reviews for a product', function () {
    $product = Product::factory()->create();
    Review::factory()->count(3)->create(['product_id' => $product->id]);

    $response = getJson("/api/v1/shop/products/{$product->id}/reviews");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
        ])
        ->assertJsonCount(3, 'data.data')
        ->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'productId',
                        'userId',
                        'userName',
                        'comment',
                        'rating',
                        'isVerifiedPurchase',
                        'createdAt',
                    ],
                ],
            ],
        ]);
});

test('getting reviews for non-existent product returns 404', function () {
    $response = getJson('/api/v1/shop/products/non-existent-id/reviews');

    $response->assertStatus(404);
});

test('unauthenticated user cannot submit review', function () {
    $product = Product::factory()->create();

    $response = postJson("/api/v1/customer/products/{$product->id}/reviews", [
        'rating' => 5,
        'comment' => 'Great product!',
    ]);

    $response->assertUnauthorized();
});

test('authenticated customer can submit a review and updates product stats', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'average_rating' => 0,
        'review_count' => 0,
    ]);

    $response = actingAs($user)->postJson("/api/v1/customer/products/{$product->id}/reviews", [
        'rating' => 4.5,
        'comment' => 'Excellent supplement, saw noticeable results!',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'productId' => $product->id,
                'userId' => (string) $user->id,
                'userName' => $user->name,
                'rating' => 4.5,
                'comment' => 'Excellent supplement, saw noticeable results!',
                'isVerifiedPurchase' => false,
            ],
        ]);

    $product->refresh();
    expect($product->review_count)->toBe(1);
    expect((float) $product->average_rating)->toBe(4.5);
});

test('verified buyer gets is_verified_purchase true', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    // Create delivered order with this product
    $order = Order::factory()->create([
        'user_id' => (string) $user->id,
        'status' => 'delivered',
        'payment_status' => 'paid',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    actingAs($user)->postJson("/api/v1/customer/products/{$product->id}/reviews", [
        'rating' => 5,
        'comment' => 'I bought it and it works great!',
    ])->assertStatus(201)
        ->assertJsonPath('data.isVerifiedPurchase', true);
});

test('submitting review again updates existing review instead of creating duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    actingAs($user)->postJson("/api/v1/customer/products/{$product->id}/reviews", [
        'rating' => 3,
        'comment' => 'Initial review',
    ])->assertStatus(201);

    expect(Review::where('product_id', $product->id)->count())->toBe(1);

    actingAs($user)->postJson("/api/v1/customer/products/{$product->id}/reviews", [
        'rating' => 5,
        'comment' => 'Updated review after longer usage!',
    ])->assertStatus(201)
        ->assertJsonPath('data.comment', 'Updated review after longer usage!');

    expect(Review::where('product_id', $product->id)->count())->toBe(1);

    $product->refresh();
    expect($product->review_count)->toBe(1);
    expect((float) $product->average_rating)->toBe(5.0);
});

test('customer can get their existing review for a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    // Before reviewing
    actingAs($user)->getJson("/api/v1/customer/products/{$product->id}/user-review")
        ->assertStatus(200)
        ->assertJsonPath('data', null);

    // After reviewing
    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'comment' => 'My previous review',
    ]);

    $response = actingAs($user)->getJson("/api/v1/customer/products/{$product->id}/user-review");
    $response->assertStatus(200)
        ->assertJsonPath('data.comment', 'My previous review');
    expect((float) $response->json('data.rating'))->toBe(4.0);
});

test('customer can delete their review and product stats update', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
    ]);

    // Recalculate stats
    $product->update(['review_count' => 1, 'average_rating' => 5]);

    actingAs($user)->deleteJson("/api/v1/customer/reviews/{$review->id}")
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    expect(Review::where('id', $review->id)->exists())->toBeFalse();

    $product->refresh();
    expect($product->review_count)->toBe(0);
    expect((float) $product->average_rating)->toBe(0.0);
});

test('customer cannot delete another user review', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create();

    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user1->id,
    ]);

    actingAs($user2)->deleteJson("/api/v1/customer/reviews/{$review->id}")
        ->assertStatus(403);
});
