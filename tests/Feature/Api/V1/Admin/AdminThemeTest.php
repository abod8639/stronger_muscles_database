<?php

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns dark as the default theme', function () {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->getJson('/api/v1/admin/profile/theme')
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'success',
            'data' => ['mode' => 'dark', 'updated_at' => null],
        ]);
});

it('saves the theme per admin', function () {
    $admin = Admin::factory()->create();
    $otherAdmin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');

    $this->putJson('/api/v1/admin/profile/theme', ['mode' => 'light', 'updated_at' => null])
        ->assertSuccessful()
        ->assertJsonPath('data.mode', 'light');

    expect($admin->fresh()->theme_mode)->toBe('light')
        ->and($admin->fresh()->theme_updated_at)->not->toBeNull()
        ->and($otherAdmin->fresh()->theme_mode)->toBe('dark');
});

it('rejects an unknown theme mode', function () {
    Sanctum::actingAs(Admin::factory()->create(), ['*'], 'admin-api');

    $this->putJson('/api/v1/admin/profile/theme', ['mode' => 'neon'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mode');
});
