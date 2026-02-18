<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

describe('ImageUploadController', function () {
    test('can delete image', function () {
        // Create a test image file
        Storage::disk('public')->put('products/test-image.jpg', 'fake image content');

        $response = $this->postJson('/api/v1/admin/upload/delete', [
            'path' => 'products/test-image.jpg',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'status' => 'success',
                'message' => 'تم حذف الصورة بنجاح',
            ]);

        Storage::disk('public')->assertMissing('products/test-image.jpg');
    });

    test('rejects delete with missing path', function () {
        $response = $this->postJson('/api/v1/admin/upload/delete', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('path');
    });

    test('rejects delete with directory traversal attempt', function () {
        $response = $this->postJson('/api/v1/admin/upload/delete', [
            'path' => '../../config/app.php',
        ]);

        $response->assertBadRequest()
            ->assertJson([
                'status' => 'error',
                'message' => 'مسار الصورة غير صحيح',
            ]);
    });

    test('rejects delete with leading slash', function () {
        $response = $this->postJson('/api/v1/admin/upload/delete', [
            'path' => '/config/app.php',
        ]);

        $response->assertBadRequest()
            ->assertJson([
                'status' => 'error',
                'message' => 'مسار الصورة غير صحيح',
            ]);
    });
});
