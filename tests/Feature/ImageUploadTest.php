<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('can upload product image', function () {
    $file = UploadedFile::fake()->image('product.jpg', 640, 480);

    $response = $this->postJson('/api/v1/upload/product-image', [
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
                'name' => 'product.jpg',
            ],
        ]);

    $path = $response->json('data.path');
    expect($path)->toMatch('/^products\//');
    Storage::disk('public')->assertExists($path);
    expect($response->json('data.url'))->toContain('/storage/products/');
});

it('can upload category image', function () {
    $file = UploadedFile::fake()->image('category.png', 640, 480);

    $response = $this->postJson('/api/v1/upload/category-image', [
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
    $file = UploadedFile::fake()->image('image.webp', 640, 480);

    $response = $this->postJson('/api/v1/upload/image', [
        'image' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
        ]);
});

it('validates image upload - missing image', function () {
    $response = $this->postJson('/api/v1/upload/product-image', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image upload - invalid file type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/api/v1/upload/product-image', [
        'image' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('validates image upload - file too large', function () {
    $file = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB (exceeds 5MB limit)

    $response = $this->postJson('/api/v1/upload/product-image', [
        'image' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('can delete image', function () {
    Storage::fake('public');
    $path = 'products/test-image.jpg';
    Storage::disk('public')->put($path, 'fake content');

    $response = $this->postJson('/api/v1/upload/delete', [
        'path' => $path,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'message' => 'تم حذف الصورة بنجاح',
        ]);

    Storage::disk('public')->assertMissing($path);
});
