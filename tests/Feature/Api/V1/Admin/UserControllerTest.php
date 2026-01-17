<?php

use App\Models\User;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can retrieve users list with stats', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $user1 = User::factory()->create();
    Order::factory()->count(2)->create(['user_id' => $user1->id, 'total_amount' => 100]); // Total 200

    $user2 = User::factory()->create();
    Order::factory()->count(1)->create(['user_id' => $user2->id, 'total_amount' => 50]);

    $response = $this->getJson('/api/v1/admin/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'total_users',
            'users' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'is_active',
                    'photo_url',
                    'total_spent',
                    'created_at',
                    'orders_count',
                    'addresses'
                ]
            ]
        ]);

    // Check specific user stats
    $userData = collect($response->json('users'))->firstWhere('email', $user1->email);
    expect($userData['orders_count'])->toBe(2)
        ->and($userData['total_spent'])->toEqual(200.0);
});

test('non-admin cannot retrieve users list', function () {
    $user = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/admin/users');

    $response->assertStatus(403);
});

test('guest cannot retrieve users list', function () {
    $response = $this->getJson('/api/v1/admin/users');

    $response->assertStatus(401);
});
