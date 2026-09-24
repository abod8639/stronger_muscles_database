<?php

use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $admin = Admin::factory()->create();
    Sanctum::actingAs($admin, ['*'], 'admin-api');
});

function getFakeImageFile(string $name = 'image.png'): UploadedFile
{
    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    return UploadedFile::fake()->createWithContent($name, $pngBytes);
}

it('can upload product image', function () {
    $file = getFakeImageFile('product.png');

    $response = $this->postJson('/api/v1/admin/upload/product-image', [
        'image' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'data' => [
                'url',
                'path',
                'name',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'data' => [
                'name' => 'product.png',
            ],
        ]);

    $path = $response->json('data.path');
    expect($path)->toMatch('/^products\//');
    Storage::disk('public')->assertExists($path);
    expect($response->json('data.url'))->toContain('/storage/products/');
});

it('can upload category image', function () {
    $file = getFakeImageFile('category.png');

    $response = $this->postJson('/api/v1/admin/upload/category-image', [
        'image' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'data' => [
                'url',
                'path',
                'name',
            ],
        ]);

    Storage::disk('public')->assertExists($response->json('data.path'));
});

it('can upload generic image', function () {
    $file = getFakeImageFile('image.png');

    $response = $this->postJson('/api/v1/admin/upload/image', [
        'image' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
        ]);
});

it('validates image upload - missing image', function () {
    $response = $this->postJson('/api/v1/admin/upload/product-image', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image upload - invalid file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/api/v1/admin/upload/product-image', [
        'image' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image upload - file too large', function () {
    $file = UploadedFile::fake()->create('large.jpg', 6000, 'image/jpeg'); // 6MB (exceeds 5MB limit)

    $response = $this->postJson('/api/v1/admin/upload/product-image', [
        'image' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('can delete image', function () {
    Storage::fake('public');
    $path = 'products/test-image.jpg';
    Storage::disk('public')->put($path, 'fake content');

    $response = $this->postJson('/api/v1/admin/upload/delete', [
        'path' => $path,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'message' => 'تم حذف الصورة بنجاح',
        ]);

    Storage::disk('public')->assertMissing($path);
});
