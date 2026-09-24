<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows customer to initiate payment for their pending order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total_amount' => 250.00,
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/customer/orders/{$order->id}/pay", [
        'gateway' => 'paymob',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'success',
        'message' => 'Payment initiated successfully',
    ]);
    expect($response->json('data.payment_url'))->not->toBeEmpty();
    expect($order->fresh()->payment_method)->toBe('paymob');
});

it('forbids customer from initiating payment on another user order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user2->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $response = $this->actingAs($user1, 'sanctum')->postJson("/api/v1/customer/orders/{$order->id}/pay", [
        'gateway' => 'paymob',
    ]);

    $response->assertNotFound();
});

it('rejects payment initiation for already paid order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/customer/orders/{$order->id}/pay", [
        'gateway' => 'paymob',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Order has already been paid.',
    ]);
});

it('rejects payment webhook with missing or invalid signature', function () {
    config(['services.paymob.hmac' => 'secret_hmac_key']);

    $response = $this->postJson('/api/v1/webhooks/payment/paymob', [
        'obj' => [
            'id' => 12345,
            'success' => 'true',
        ],
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Invalid webhook signature',
    ]);
});

it('processes valid paymob webhook and updates order status to paid', function () {
    config(['services.paymob.hmac' => 'secret_hmac_key']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total_amount' => 150.00,
    ]);

    $obj = [
        'amount_cents' => '15000',
        'created_at' => '2026-09-24T10:00:00',
        'currency' => 'EGP',
        'error_occured' => 'false',
        'has_parent_transaction' => 'false',
        'id' => '998877',
        'integration_id' => '12345',
        'is_3d_secure' => 'true',
        'is_auth' => 'false',
        'is_capture' => 'false',
        'is_refunded' => 'false',
        'is_standalone_payment' => 'true',
        'is_voided' => 'false',
        'order' => [
            'id' => 'paymob_ord_1',
            'merchant_order_id' => (string) $order->id,
        ],
        'owner' => '1',
        'pending' => 'false',
        'source_data' => [
            'pan' => '2345',
            'sub_type' => 'MasterCard',
            'type' => 'card',
        ],
        'success' => 'true',
    ];

    $concatenated =
        $obj['amount_cents'].
        $obj['created_at'].
        $obj['currency'].
        $obj['error_occured'].
        $obj['has_parent_transaction'].
        $obj['id'].
        $obj['integration_id'].
        $obj['is_3d_secure'].
        $obj['is_auth'].
        $obj['is_capture'].
        $obj['is_refunded'].
        $obj['is_standalone_payment'].
        $obj['is_voided'].
        $obj['order']['id'].
        $obj['owner'].
        $obj['pending'].
        $obj['source_data']['pan'].
        $obj['source_data']['sub_type'].
        $obj['source_data']['type'].
        $obj['success'];

    $hmac = hash_hmac('sha512', $concatenated, 'secret_hmac_key');

    $response = $this->postJson("/api/v1/webhooks/payment/paymob?hmac={$hmac}", [
        'obj' => $obj,
    ]);

    $response->assertSuccessful();
    expect($order->fresh()->payment_status)->toBe('paid');
    expect($order->fresh()->status)->toBe('processing');
    expect($order->fresh()->transaction_id)->toBe('998877');
});

it('handles idempotent duplicate webhooks gracefully', function () {
    config(['services.paymob.hmac' => 'secret_hmac_key']);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    $obj = [
        'amount_cents' => '10000',
        'created_at' => '2026-09-24T10:00:00',
        'currency' => 'EGP',
        'error_occured' => 'false',
        'has_parent_transaction' => 'false',
        'id' => '998877',
        'integration_id' => '12345',
        'is_3d_secure' => 'true',
        'is_auth' => 'false',
        'is_capture' => 'false',
        'is_refunded' => 'false',
        'is_standalone_payment' => 'true',
        'is_voided' => 'false',
        'order' => [
            'id' => 'paymob_ord_1',
            'merchant_order_id' => (string) $order->id,
        ],
        'owner' => '1',
        'pending' => 'false',
        'source_data' => [
            'pan' => '2345',
            'sub_type' => 'MasterCard',
            'type' => 'card',
        ],
        'success' => 'true',
    ];

    $concatenated =
        $obj['amount_cents'].
        $obj['created_at'].
        $obj['currency'].
        $obj['error_occured'].
        $obj['has_parent_transaction'].
        $obj['id'].
        $obj['integration_id'].
        $obj['is_3d_secure'].
        $obj['is_auth'].
        $obj['is_capture'].
        $obj['is_refunded'].
        $obj['is_standalone_payment'].
        $obj['is_voided'].
        $obj['order']['id'].
        $obj['owner'].
        $obj['pending'].
        $obj['source_data']['pan'].
        $obj['source_data']['sub_type'].
        $obj['source_data']['type'].
        $obj['success'];

    $hmac = hash_hmac('sha512', $concatenated, 'secret_hmac_key');

    $response = $this->postJson("/api/v1/webhooks/payment/paymob?hmac={$hmac}", [
        'obj' => $obj,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe('already_processed');
});

it('processes valid stripe webhook and updates order', function () {
    $secret = 'whsec_test_secret';
    config(['services.stripe.webhook_secret' => $secret]);

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $payloadArray = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_123456',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    $response = $this->call(
        'POST',
        '/api/v1/webhooks/payment/stripe',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    );

    $response->assertSuccessful();
    expect($order->fresh()->payment_status)->toBe('paid');
    expect($order->fresh()->status)->toBe('processing');
    expect($order->fresh()->transaction_id)->toBe('pi_test_123456');
});

it('allows user to register and update device fcm token', function () {
    $user = User::factory()->create(['fcm_token' => null]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/customer/fcm-token', [
        'fcm_token' => 'device_token_xyz_123',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'success',
        'message' => 'FCM token updated successfully',
    ]);
    expect($user->fresh()->fcm_token)->toBe('device_token_xyz_123');
});
