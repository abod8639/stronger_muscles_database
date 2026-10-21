<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
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
     * Get paginated reviews for a specific product.
     */
    public function index(Request $request, string $productId): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 10);
            $reviews = $this->reviewService->getProductReviews($productId, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => ReviewResource::collection($reviews)->response()->getData(true),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load reviews',
            ], 500);
        }
    }
}
