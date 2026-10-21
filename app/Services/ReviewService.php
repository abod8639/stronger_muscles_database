<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

class ReviewService
{
    public function __construct(
        protected ReviewRepository $reviewRepository
    ) {}

    /**
     * Get paginated reviews for a product.
     */
    public function getProductReviews(string $productId, int $perPage = 10): LengthAwarePaginator
    {
        // Ensure product exists
        if (! Product::where('id', $productId)->exists()) {
            throw new ModelNotFoundException("Product not found with id: {$productId}");
        }

        return $this->reviewRepository->getPaginatedForProduct($productId, $perPage);
    }

    /**
     * Get a specific user's review for a product.
     */
    public function getUserReview(string $productId, User $user): ?Review
    {
        return $this->reviewRepository->findByProductAndUser($productId, $user->id);
    }

    /**
     * Store or update a review from an authenticated user.
     */
    public function storeOrUpdateReview(User $user, string $productId, array $data): Review
    {
        $product = Product::where('id', $productId)->first();
        if (! $product) {
            throw new ModelNotFoundException("Product not found with id: {$productId}");
        }

        $isVerified = $this->isUserVerifiedBuyer($productId, $user->id);

        $existingReview = $this->reviewRepository->findByProductAndUser($productId, $user->id);

        if ($existingReview) {
            $review = $this->reviewRepository->update($existingReview, [
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'is_verified_purchase' => $isVerified,
            ]);
        } else {
            $review = $this->reviewRepository->create([
                'product_id' => $productId,
                'user_id' => $user->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'is_verified_purchase' => $isVerified,
            ]);
            $review->load('user:id,name,photo_url');
        }

        $this->recalculateProductRating($productId);

        return $review;
    }

    /**
     * Delete a review.
     */
    public function deleteReview(Review $review): bool
    {
        $productId = $review->product_id;
        $deleted = $this->reviewRepository->delete($review);

        if ($deleted) {
            $this->recalculateProductRating($productId);
        }

        return $deleted;
    }

    /**
     * Check if user is a verified buyer of this product.
     */
    public function isUserVerifiedBuyer(string $productId, int|string $userId): bool
    {
        return Order::where('user_id', (string) $userId)
            ->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhereIn('status', ['completed', 'delivered', 'shipped']);
            })
            ->whereHas('orderItems', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }

    /**
     * Recalculate average_rating and review_count on the product and clear cache.
     */
    public function recalculateProductRating(string $productId): void
    {
        $stats = $this->reviewRepository->getRatingStats($productId);

        $count = (int) ($stats->count ?? 0);
        $avg = $count > 0 ? round((float) ($stats->avg_rating ?? 0), 2) : 0.0;

        Product::where('id', $productId)->update([
            'review_count' => $count,
            'average_rating' => $avg,
        ]);

        Cache::forget("product:{$productId}");
    }
}
