<?php

use App\Models\User;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('can update basic profile info', function () {
  $user = User::factory()->create([
    'name' => 'Old Name',
    'email' => 'old@example.com',
    'phone_number' => '1234567890',
  ]);

  $response = $this->actingAs($user)->postJson('/api/v1/auth/update-profile', [
    'name' => 'New Name',
    'email' => 'new@example.com',
    'phone' => '0987654321',
  ]);

  $response->assertStatus(200)
    ->assertJson([
      'status' => 'success',
      'user' => [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'phone' => '0987654321',
      ],
    ]);

  $this->assertDatabaseHas('users', [
    'id' => $user->id,
    'name' => 'New Name',
    'email' => 'new@example.com',
    'phone_number' => '0987654321',
  ]);
});

it('can update addresses', function () {
  $user = User::factory()->create();

  // Create initial address linked to user (if any factory exists or manually)
  // Assuming Address factory exists, if not we can manually create, but let's test creating new ones mainly

  $addressesData = [
    [
      'label' => 'Home',
      'full_name' => 'Test User',
      'phone' => '1234567890',
      'street' => '123 Main St',
      'city' => 'Anytown',
      'state' => 'State',
      'country' => 'Country',
    ],
    [
      'label' => 'Work',
      'full_name' => 'Test User Work',
      'phone' => '0987654321',
      'street' => '456 Corp Blvd',
      'city' => 'Big City',
      'state' => 'State',
      'country' => 'Country',
    ]
  ];

  $response = $this->actingAs($user)->postJson('/api/v1/auth/update-profile', [
    'addresses' => $addressesData,
  ]);

  $response->assertStatus(200);

  $this->assertCount(2, $user->addresses);
  $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'label' => 'Home']);
  $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'label' => 'Work']);
});

it('validates unique email', function () {
  $user1 = User::factory()->create(['email' => 'user1@example.com']);
  $user2 = User::factory()->create(['email' => 'user2@example.com']);

  $response = $this->actingAs($user1)->postJson('/api/v1/auth/update-profile', [
    'email' => 'user2@example.com',
  ]);

  $response->assertStatus(422)
    ->assertJsonValidationErrors(['email']);
});

it('allows updating to own email', function () {
  $user = User::factory()->create(['email' => 'user@example.com']);

  $response = $this->actingAs($user)->postJson('/api/v1/auth/update-profile', [
    'email' => 'user@example.com',
  ]);

  $response->assertStatus(200);
});
