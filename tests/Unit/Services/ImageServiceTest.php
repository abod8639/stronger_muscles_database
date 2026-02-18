<?php

use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

describe('ImageService', function () {
    test('can delete image', function () {
        $imageService = app(ImageService::class);

        // Create a test file directly
        Storage::disk('public')->put('images/test.jpg', 'fake image content');

        $deleted = $imageService->delete('images/test.jpg');

        expect($deleted)->toBeTrue()
            ->and(Storage::disk('public')->exists('images/test.jpg'))->toBeFalse();
    });

    test('returns false when deleting non-existent image', function () {
        $imageService = app(ImageService::class);
        $deleted = $imageService->delete('non-existent-path.jpg');

        expect($deleted)->toBeFalse();
    });

    test('can delete multiple images', function () {
        $imageService = app(ImageService::class);

        Storage::disk('public')->put('images/test1.jpg', 'fake image 1');
        Storage::disk('public')->put('images/test2.jpg', 'fake image 2');

        $deleted = $imageService->deleteMultiple(['images/test1.jpg', 'images/test2.jpg']);

        expect($deleted)->toBe(2)
            ->and(Storage::disk('public')->exists('images/test1.jpg'))->toBeFalse()
            ->and(Storage::disk('public')->exists('images/test2.jpg'))->toBeFalse();
    });

    test('throws exception for invalid path on delete', function () {
        $imageService = app(ImageService::class);
        $imageService->delete('../../config/app.php');
    })->throws(\InvalidArgumentException::class);

    test('can check if image exists', function () {
        $imageService = app(ImageService::class);

        Storage::disk('public')->put('images/test.jpg', 'fake content');

        expect($imageService->exists('images/test.jpg'))->toBeTrue()
            ->and($imageService->exists('non-existent.jpg'))->toBeFalse();
    });

    test('has validation rules for single image', function () {
        $imageService = app(ImageService::class);
        $rules = $imageService->getValidationRules();

        expect($rules)
            ->toHaveKey('image')
            ->and($rules['image'])->toContain('required')
            ->and($rules['image'])->toContain('image')
            ->and($rules['image'])->toContain('mimes:jpeg,png,jpg,gif,webp')
            ->and($rules['image'])->toContain('max:5120');
    });

    test('has validation rules for multiple images', function () {
        $imageService = app(ImageService::class);
        $rules = $imageService->getMultipleValidationRules();

        expect($rules)
            ->toHaveKeys(['images', 'images.*'])
            ->and($rules['images'])->toContain('required')
            ->and($rules['images'])->toContain('array')
            ->and($rules['images'])->toContain('min:1')
            ->and($rules['images.*'])->toContain('image')
            ->and($rules['images.*'])->toContain('mimes:jpeg,png,jpg,gif,webp')
            ->and($rules['images.*'])->toContain('max:5120');
    });

    test('generates correct image url', function () {
        $imageService = app(ImageService::class);
        $url = $imageService->getImageUrl('products/test.jpg');

        expect($url)->toEndWith('/storage/products/test.jpg')
            ->and($url)->toStartWith(config('app.url'));
    });
});
