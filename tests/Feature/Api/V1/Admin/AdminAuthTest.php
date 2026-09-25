<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Admin Authentication', function () {
    test('new admin can register successfully', function () {
        $response = $this->postJson('/api/v1/admin/register', [
            'name' => 'New Admin',
            'email' => 'newadmin@test.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user.email', 'newadmin@test.com')
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('admins', [
            'email' => 'newadmin@test.com',
            'role' => 'admin',
        ]);
    });

    test('admin registration validates required fields and unique email', function () {
        Admin::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/v1/admin/register', [
            'name' => '',
            'email' => 'existing@test.com',
            'password' => '123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    test('admin can login successfully with valid credentials', function () {
        Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['token', 'user']);
    });

    test('admin login fails with wrong credentials', function () {
        Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpass',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('status', 'error');
    });
});
