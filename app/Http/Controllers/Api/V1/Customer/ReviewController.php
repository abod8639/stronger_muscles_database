<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Review\StoreReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Submit or update a review for a product.
     */
    public function store(StoreReviewRequest $request, string $productId): JsonResponse
    {
        try {
            $user = $request->user();
            $review = $this->reviewService->storeOrUpdateReview($user, $productId, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'تم حفظ التقييم بنجاح',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save review: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the authenticated user's review for a product.
     */
    public function userReview(Request $request, string $productId): JsonResponse
    {
        $user = $request->user();
        $review = $this->reviewService->getUserReview($productId, $user);

        if (! $review) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $review = Review::where('id', $id)->first();

        if (! $review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found',
            ], 404);
        }

        if ((string) $review->user_id !== (string) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action',
            ], 403);
        }

        $this->reviewService->deleteReview($review);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف التقييم بنجاح',
        ]);
    }
}
