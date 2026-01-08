<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('google sign in creates new user', function () {
    $response = $this->postJson('/api/v1/auth/google-signin', [
        'email' => 'newuser@example.com',
        'name' => 'New User',
        'photo_url' => 'https://example.com/photo.jpg',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'name', 'email', 'photo_url'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'photo_url' => 'https://example.com/photo.jpg',
    ]);
});

test('google sign in logs in existing user', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'photo_url' => 'https://original.com/photo.jpg',
    ]);

    $response = $this->postJson('/api/v1/auth/google-signin', [
        'email' => 'existing@example.com',
        'name' => 'Existing User',
        'photo_url' => 'https://new.com/photo.jpg', // New photo
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'email' => 'existing@example.com',
        ]);

    // Check if photo_url was updated
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'photo_url' => 'https://new.com/photo.jpg',
    ]);
});

test('google sign in requires email and name', function () {
    $response = $this->postJson('/api/v1/auth/google-signin', [
        // Missing data
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'name']);
});
