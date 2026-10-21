<?php

namespace App\Repositories;

use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewRepository
{
    /**
     * Get paginated reviews for a specific product with user info.
     */
    public function getPaginatedForProduct(string $productId, int $perPage = 10): LengthAwarePaginator
    {
        return Review::forProduct($productId)
            ->with('user:id,name,photo_url')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a user's existing review for a specific product.
     */
    public function findByProductAndUser(string $productId, int|string $userId): ?Review
    {
        return Review::forProduct($productId)
            ->where('user_id', $userId)
            ->with('user:id,name,photo_url')
            ->first();
    }

    /**
     * Find review by ID.
     */
    public function findById(string $id): ?Review
    {
        return Review::with('user:id,name,photo_url')->find($id);
    }

    /**
     * Create a new review.
     */
    public function create(array $data): Review
    {
        return Review::create($data);
    }

    /**
     * Update an existing review.
     */
    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review->fresh('user:id,name,photo_url');
    }

    /**
     * Delete a review.
     */
    public function delete(Review $review): bool
    {
        return (bool) $review->delete();
    }

    /**
     * Get rating statistics (count and average) for a product.
     */
    public function getRatingStats(string $productId): object
    {
        return Review::forProduct($productId)
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg_rating')
            ->first() ?? (object) ['count' => 0, 'avg_rating' => 0];
    }
}
