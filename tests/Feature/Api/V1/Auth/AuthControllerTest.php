<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('user can register', function () {
    $response = postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'token',
            'user' => [
                'id', 'name', 'email', 'role', 'created_at'
            ],
        ]);
        
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('user can login', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
});

test('user can update profile', function () {
    $user = User::factory()->create();
    
    $response = actingAs($user)->postJson('/api/v1/auth/update-profile', [
        'name' => 'Updated Name',
        'addresses' => [
            [
                'street' => '123 Main St',
                'city' => 'Metropolis',
            ]
        ]
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('user.name', 'Updated Name');
        
    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'street' => '123 Main St',
    ]);
});

test('user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    // We can't easily inject the specific token to be currentAccessToken with actingAs simply for logout 
    // unless we use Sanctum's actingAs which sets the token.
    // However, actingAs($user) sets the user, and if we use Sanctum, it should work for currentAccessToken() if set up right.
    // A more explicit way for API token test:
    
    $response = postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);
        
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
