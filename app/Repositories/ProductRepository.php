<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductRepository
{
    /**
     * Get paginated products for the public catalog.
     */
    public function getPaginatedPublic(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::active()
            ->forListView()
            ->withCategoryData();

        if (! empty($filters['category'])) {
            $query->category($filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $sortBy = $filters['sort_by'] ?? 'latest';

        $query = match ($sortBy) {
            'price_low' => $query->sortByPrice('asc'),
            'price_high' => $query->sortByPrice('desc'),
            'best_seller' => $query->sortByPopularity(),
            'rating' => $query->sortByRating(),
            'new' => $query->newArrivals()->latest('created_at'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Find an active product for detail view.
     */
    public function findActiveById(string $id): Product
    {
        return Product::active()
            ->forDetailView()
            ->withCategoryData()
            ->with('variants:id,product_id,sku,price,discount_price,stock_quantity,attributes')
            ->findOrFail($id);
    }

    /**
     * Get paginated products for the admin panel.
     */
    public function getPaginatedAdmin(int $perPage = 10): LengthAwarePaginator
    {
        return Product::with(['category', 'variants'])->latest()->paginate($perPage);
    }

    /**
     * Find a product by ID with relationships.
     */
    public function findOrFailWithRelations(string $id, array $relations = ['category', 'variants']): Product
    {
        return Product::with($relations)->findOrFail($id);
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Sync product variants.
     */
    public function syncVariants(Product $product, array $variantsData): void
    {
        $existingIds = collect($variantsData)->pluck('id')->filter()->toArray();
        $product->variants()->whereNotIn('id', $existingIds)->delete();

        foreach ($variantsData as $variantData) {
            $variantId = $variantData['id'] ?? (string) Str::uuid();
            $product->variants()->updateOrCreate(
                ['id' => $variantId],
                [
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'discount_price' => $variantData['discount_price'] ?? null,
                    'discount_start_date' => $variantData['discount_start_date'] ?? null,
                    'discount_end_date' => $variantData['discount_end_date'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'],
                    'attributes' => $variantData['attributes'],
                    'is_active' => $variantData['is_active'] ?? true,
                ]
            );
        }
    }
}
