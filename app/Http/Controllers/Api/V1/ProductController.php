<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of active products with filtering and sorting.
     */
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'latest');
        $page = $request->query('page', 1);

        // More fine tuned cache key
        $cacheKey = 'products:list:'.md5("cat={$category}&search={$search}&sort={$sortBy}&page={$page}");

        $products = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $search, $sortBy) {
            $query = Product::active()
                ->forListView()
                ->withCategoryData();

            // Filtering by Category
            if ($category) {
                $query->category($category);
            }

            // Search
            if ($search) {
                $query->search($search);
            }

            // Sorting
            $query = match ($sortBy) {
                'price_low' => $query->sortByPrice('asc'),
                'price_high' => $query->sortByPrice('desc'),
                'best_seller' => $query->sortByPopularity(),
                'rating' => $query->sortByRating(),
                'new' => $query->newArrivals()->latest('created_at'),
                default => $query->orderBy('created_at', 'desc'),
            };

            return $query->paginate(20);
        });

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        try {
            // Cache individual product for 60 minutes
            $product = Cache::remember("product:{$id}", now()->addHours(1), function () use ($id) {
                return Product::active()
                    ->forDetailView()
                    ->withCategoryData()
                    ->with('variants:id,product_id,sku,price,discount_price,stock_quantity,attributes')
                    ->findOrFail($id);
            });

            return response()->json([
                'status' => 'success',
                'data' => new ProductResource($product),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or inactive',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
