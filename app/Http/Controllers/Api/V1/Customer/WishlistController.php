<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Wishlist\StoreWishlistItemRequest;
use App\Http\Resources\Api\V1\WishlistItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    /**
     * List the authenticated user's wishlist, newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $request->user()
            ->wishlistItems()
            ->with('product:id,name,price,discount_price,image_urls')
            ->latest()
            ->get();

        return WishlistItemResource::collection($items)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Add a product to the wishlist; adding it twice keeps a single entry.
     */
    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        $item = $request->user()
            ->wishlistItems()
            ->firstOrCreate(['product_id' => $request->validated('product_id')]);

        return response()->json([
            'status' => 'success',
            'data' => new WishlistItemResource($item->load('product:id,name,price,discount_price,image_urls')),
        ], $item->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Remove a product from the wishlist by its product id.
     */
    public function destroy(Request $request, string $productId): JsonResponse
    {
        $request->user()->wishlistItems()->where('product_id', $productId)->delete();

        return response()->json([
            'status' => 'success',
        ]);
    }
}
