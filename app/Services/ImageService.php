<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    protected const ALLOWED_MIMES = ['jpeg', 'png', 'jpg', 'gif', 'webp'];

    protected const ALLOWED_EXTENSIONS = 'jpeg,png,jpg,gif,webp';

    protected const MAX_FILE_SIZE = 5120; // 5MB in KB

    /**
     * Upload an image to the specified folder.
     *
     * @return array{url: string, path: string, name: string, size: int}
     */
    public function upload(UploadedFile $file, string $folder = 'images'): array
    {
        // Generate unique filename with UUID
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $folder.'/'.$fileName;

        // Store file on public disk
        $saved = Storage::disk('public')->put($path, $file->get());

        if (! $saved) {
            throw new \RuntimeException('Failed to save image to storage');
        }

        // Generate URL directly for better performance
        $url = $this->getImageUrl($path);

        return [
            'url' => $url,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Upload multiple images to the specified folder.
     *
     * @param  array<UploadedFile>  $files
     * @return array<array{url: string, path: string, name: string, size: int}>
     */
    public function uploadMultiple(array $files, string $folder = 'images'): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->upload($file, $folder);
        }

        return $results;
    }

    /**
     * Delete an image from storage.
     */
    public function delete(string $path): bool
    {
        // Validate path to prevent directory traversal
        if ($this->isInvalidPath($path)) {
            throw new \InvalidArgumentException('Invalid image path');
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Delete multiple images from storage.
     *
     * @param  array<string>  $paths
     * @return int Number of deleted files
     */
    public function deleteMultiple(array $paths): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get the full URL for an image.
     */
    public function getImageUrl(string $path): string
    {
        return config('app.url').'/storage/'.$path;
    }

    /**
     * Check if image exists in storage.
     */
    public function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Get validation rules for image upload.
     *
     * @return array<string, string>
     */
    public function getValidationRules(): array
    {
        return [
            'image' => 'required|image|mimes:'.self::ALLOWED_EXTENSIONS.'|max:'.self::MAX_FILE_SIZE,
        ];
    }

    /**
     * Get validation rules for multiple image uploads.
     *
     * @return array<string, string>
     */
    public function getMultipleValidationRules(): array
    {
        return [
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:'.self::ALLOWED_EXTENSIONS.'|max:'.self::MAX_FILE_SIZE,
        ];
    }

    /**
     * Validate that the path is safe (prevent directory traversal).
     */
    protected function isInvalidPath(string $path): bool
    {
        // Check for directory traversal attempts
        if (str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }

        // Check for absolute paths
        if (preg_match('#^[a-zA-Z]:#', $path)) {
            return true; // Windows absolute path
        }

        return false;
    }
}
