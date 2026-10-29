<?php

use App\Models\Address;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

/**
 * Pins the JSON types that the Flutter app and dashboard parse with strict casts
 * (e.g. `json['product_id'] as String`), so schema changes cannot silently break them.
 */
uses(RefreshDatabase::class);

/**
 * Creates an order whose product and address were deleted after checkout.
 */
function orderWithDeletedProductAndAddress(): Order
{
    $orderItem = OrderItem::factory()->create();
    $order = $orderItem->order;

    Product::query()->whereKey($orderItem->product_id)->delete();
    Address::query()->whereKey($order->address_id)->delete();

    return $order->fresh();
}

it('returns string ids to the customer app even after the product and address are deleted', function () {
    $order = orderWithDeletedProductAndAddress();
    Sanctum::actingAs($order->user);

    $this->getJson('/api/v1/customer/orders')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.0', fn (AssertableJson $json) => $json
                ->whereType('id', 'string')
                ->whereType('user_id', 'string')
                ->whereType('address_id', 'string')
                ->has('order_items.0', fn (AssertableJson $json) => $json
                    ->whereType('id', 'string')
                    ->whereType('order_id', 'string')
                    ->whereType('product_id', 'string')
                    ->whereType('product_name', 'string')
                    ->etc())
                ->etc())
            ->etc());
});

it('returns string ids to the dashboard even after the product and address are deleted', function () {
    $order = orderWithDeletedProductAndAddress();
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->getJson("/api/v1/admin/orders/{$order->id}")
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $json) => $json
                ->whereType('id', 'string')
                ->whereType('address_id', 'string')
                ->has('order_items.0', fn (AssertableJson $json) => $json
                    ->whereType('order_id', 'string')
                    ->whereType('product_id', 'string')
                    ->whereType('product_name', 'string')
                    ->etc())
                ->etc())
            ->etc());
});

it('returns the default address id as a string on the customer profile', function (?bool $hasDefaultAddress) {
    $user = User::factory()->create();

    if ($hasDefaultAddress) {
        $address = Address::factory()->default()->create(['user_id' => $user->id]);
        $user->update(['default_address_id' => $address->id]);
    }

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->whereType('user.id', 'string')
            ->whereType('user.default_address_id', $hasDefaultAddress ? 'string' : 'null')
            ->etc());
})->with([
    'with a default address' => true,
    'without a default address' => false,
]);

it('remembers the default address created through the api', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/customer/addresses', [
        'street' => '123 Main St',
        'city' => 'Cairo',
        'is_default' => true,
    ])->assertSuccessful();

    $defaultAddressId = $user->addresses()->sole()->id;

    expect($user->fresh()->default_address_id)->toBe($defaultAddressId);

    $this->getJson('/api/v1/customer/profile')
        ->assertSuccessful()
        ->assertJsonPath('user.default_address_id', (string) $defaultAddressId);
});
