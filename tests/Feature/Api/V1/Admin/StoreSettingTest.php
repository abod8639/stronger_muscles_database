<?php

use App\Models\Address;
use App\Models\Admin;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Places an order through the customer API for a single product at the given price.
 */
function placeOrderFor(float $price): Illuminate\Testing\TestResponse
{
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['price' => $price, 'discount_price' => null, 'stock_quantity' => 10]);
    Sanctum::actingAs($user);

    return test()->postJson('/api/v1/customer/orders', [
        'address_id' => $address->id,
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
}

it('returns default settings with numeric money fields', function () {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->getJson('/api/v1/admin/settings')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('status', 'success')
            ->has('data', fn (AssertableJson $json) => $json
                ->where('store_name', 'Stronger Muscles')
                ->where('currency', 'EGP')
                ->whereType('delivery_fee', 'integer|double')
                ->where('delivery_fee', 50)
                ->whereType('free_delivery_threshold', 'integer|double')
                ->where('is_store_open', true)
                ->where('maintenance_mode', false)
                ->where('allowed_admin_roles', StoreSetting::DEFAULT_ADMIN_ROLES)
                ->etc()));

    expect(StoreSetting::query()->count())->toBe(1);
});

it('updates settings and ignores unknown fields', function () {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->putJson('/api/v1/admin/settings', [
        'store_name' => 'Stronger Muscles Cairo',
        'delivery_fee' => 75.5,
        'free_delivery_threshold' => 1000,
        'is_store_open' => false,
        'allowed_admin_roles' => ['super_admin'],
        'updated_at' => '2020-01-01T00:00:00Z',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.store_name', 'Stronger Muscles Cairo')
        ->assertJsonPath('data.delivery_fee', 75.5)
        ->assertJsonPath('data.is_store_open', false)
        ->assertJsonPath('data.allowed_admin_roles', ['super_admin']);

    expect(StoreSetting::current()->free_delivery_threshold)->toBe('1000.00');
});

it('validates settings', function (array $payload, string $field) {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->putJson('/api/v1/admin/settings', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'negative delivery fee' => [['delivery_fee' => -1], 'delivery_fee'],
    'text delivery fee' => [['delivery_fee' => 'free'], 'delivery_fee'],
    'invalid email' => [['support_email' => 'not-an-email'], 'support_email'],
    'unknown role' => [['allowed_admin_roles' => ['owner']], 'allowed_admin_roles.0'],
    'no roles' => [['allowed_admin_roles' => []], 'allowed_admin_roles'],
]);

it('lets only admins and super admins change settings', function () {
    Sanctum::actingAs(Admin::factory()->customerSupport()->create(), ['*'], 'admin-api');

    $this->getJson('/api/v1/admin/settings')->assertSuccessful();
    $this->putJson('/api/v1/admin/settings', ['delivery_fee' => 0])->assertForbidden();
});

it('charges the configured delivery fee at checkout', function () {
    StoreSetting::factory()->create(['delivery_fee' => 30]);

    placeOrderFor(100)
        ->assertCreated()
        ->assertJsonPath('data.shippingCost', 30)
        ->assertJsonPath('data.total_amount', 130);
});

it('keeps charging 50 for delivery until an admin changes it', function () {
    placeOrderFor(100)
        ->assertCreated()
        ->assertJsonPath('data.shippingCost', 50)
        ->assertJsonPath('data.total_amount', 150);
});

it('waives delivery once the free delivery threshold is reached', function (int $price, int $expectedShipping) {
    StoreSetting::factory()->create(['delivery_fee' => 50, 'free_delivery_threshold' => 500]);

    placeOrderFor($price)
        ->assertCreated()
        ->assertJsonPath('data.shippingCost', $expectedShipping);
})->with([
    'below threshold' => [499, 50],
    'at threshold' => [500, 0],
    'above threshold' => [800, 0],
]);

it('rejects orders while the store is closed or in maintenance', function (array $state) {
    StoreSetting::factory()->create($state);

    placeOrderFor(100)
        ->assertBadRequest()
        ->assertJsonPath('status', 'error');

    expect(Product::query()->sole()->stock_quantity)->toBe(10);
})->with([
    'closed' => [['is_store_open' => false]],
    'maintenance' => [['maintenance_mode' => true]],
]);
