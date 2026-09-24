<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * Get public products with caching.
     */
    public function getPublicProducts(array $filters = [], int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $category = $filters['category'] ?? '';
        $search = $filters['search'] ?? '';
        $sortBy = $filters['sort_by'] ?? 'latest';

        $cacheKey = 'products:list:'.md5("cat={$category}&search={$search}&sort={$sortBy}&page={$page}");

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters, $perPage) {
            return $this->productRepository->getPaginatedPublic($filters, $perPage);
        });
    }

    /**
     * Get public product details with caching.
     */
    public function getProductDetails(string $id): Product
    {
        return Cache::remember("product:{$id}", now()->addHours(1), function () use ($id) {
            return $this->productRepository->findActiveById($id);
        });
    }

    /**
     * Get products for admin panel.
     */
    public function getAdminProducts(int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepository->getPaginatedAdmin($perPage);
    }

    /**
     * Get product for admin panel.
     */
    public function getAdminProduct(string $id): Product
    {
        return $this->productRepository->findOrFailWithRelations($id);
    }

    /**
     * Create a product and associated variants.
     */
    public function createProduct(array $data, array $variantsData = []): Product
    {
        $data = $this->normalizeProductData($data);

        $product = $this->productRepository->create($data);

        if (! empty($variantsData)) {
            $this->productRepository->syncVariants($product, $variantsData);
        }

        $this->clearCaches($product->id);

        return $this->productRepository->findOrFailWithRelations($product->id);
    }

    /**
     * Update product and optionally sync variants.
     */
    public function updateProduct(string $id, array $data, ?array $variantsData = null): Product
    {
        $product = $this->productRepository->findOrFailWithRelations($id);

        $data = $this->normalizeProductData($data);

        $this->productRepository->update($product, $data);

        if ($variantsData !== null) {
            $this->productRepository->syncVariants($product, $variantsData);
        }

        $this->clearCaches($id);

        return $this->productRepository->findOrFailWithRelations($id);
    }

    /**
     * Delete product.
     */
    public function deleteProduct(string $id): bool
    {
        $product = $this->productRepository->findOrFailWithRelations($id);

        $deleted = $this->productRepository->delete($product);

        $this->clearCaches($id);

        return $deleted;
    }

    /**
     * Normalize image URLs and attributes.
     */
    protected function normalizeProductData(array $data): array
    {
        if (isset($data['image_urls']) && is_array($data['image_urls'])) {
            $data['image_urls'] = collect($data['image_urls'])->map(function ($img) {
                if (is_string($img)) {
                    return ['thumbnail' => $img, 'medium' => $img, 'original' => $img];
                }

                return $img;
            })->values()->toArray();
        }

        return $data;
    }

    /**
     * Clear application caches relating to products.
     */
    public function clearCaches(?string $productId = null): void
    {
        if ($productId) {
            Cache::forget("product:{$productId}");
        }

        Artisan::call('cache:clear');
    }
}
